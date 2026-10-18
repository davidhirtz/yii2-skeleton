<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Controllers;

use Hirtz\Skeleton\Mcp\JsonRpcException;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Server;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\User as WebUser;
use JsonException;
use Yii;
use yii\base\Module;
use yii\web\Response;

/**
 * The stateless Streamable HTTP transport: one JSON-RPC message per POST, answered as JSON, authenticated by a
 * token `mcp/token` or the OAuth token endpoint issued.
 *
 * @extends Controller<Module>
 */
class McpController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex(): Response
    {
        $mcp = Mcp::getComponent();

        if (!$this->isAllowedOrigin($mcp)) {
            return $this->respond(403, new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Origin not allowed'));
        }

        if (!$this->request->getIsPost()) {
            $this->response->getHeaders()->set('Allow', 'POST');
            return $this->respond(405);
        }

        $bearer = $this->getBearerToken();
        $token = $bearer !== null ? $mcp->findToken($bearer, $mcp->getResourceUrl()) : null;

        if ($token === null) {
            if ($bearer !== null && $mcp->isUnknownToken($bearer) && !$mcp->countAuthenticationAttempt((string)$this->request->getUserIP())) {
                $this->response->getHeaders()->set('Retry-After', (string)$mcp->authenticationAttemptDuration);
                return $this->respond(429);
            }

            $this->response->getHeaders()->set('WWW-Authenticate', $this->getAuthenticateChallenge($mcp, $bearer !== null));
            return $this->respond(401);
        }

        WebUser::setSessionlessIdentity($token->user);
        $mcp->setClient($token->grant ? $token->grant->getClientHost() : "MCP token #$token->id");

        Yii::info("token=$token->id client={$mcp->getClient()}", 'mcp');

        try {
            $message = json_decode($this->request->getRawBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->respond(400, new JsonRpcException(JsonRpcException::PARSE_ERROR, 'Parse error'));
        }

        $isModern = $this->isModernRequest($message);

        if ($exception = $this->validateHeaders($message, $isModern)) {
            return $this->respond(400, $exception, $message);
        }

        $server = $mcp->createServer($token->type === UserToken::TYPE_MCP_READ);
        $response = $server->handle($message);

        if ($response === null) {
            return $this->respond(202);
        }

        $code = is_array($response['error'] ?? null) ? ($response['error']['code'] ?? null) : null;

        return $this->respond($this->getStatusCode($code, $isModern), $response);
    }

    /**
     * The spec's guard against DNS rebinding: a browser always sends `Origin`, an agent usually none.
     */
    protected function isAllowedOrigin(Mcp $mcp): bool
    {
        $origin = $this->request->getOrigin();

        return $origin === null
            || $origin === $this->request->getHostInfo()
            || in_array($origin, $mcp->allowedOrigins, true);
    }

    protected function getAuthenticateChallenge(Mcp $mcp, bool $isInvalid): string
    {
        $params = $isInvalid ? ['error' => 'invalid_token'] : [];

        if ($mcp->isOAuthEnabled()) {
            $params['resource_metadata'] = $mcp->getResourceMetadataUrl();
            $params['scope'] = OAuthGrant::SCOPE_WRITE;
        }

        $params = array_map(fn (string $name, string $value): string => "$name=\"$value\"", array_keys($params), $params);

        return rtrim('Bearer ' . implode(', ', $params));
    }

    protected function getBearerToken(): ?string
    {
        $header = (string)$this->request->getHeaders()->get('Authorization', '');
        return preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) ? $matches[1] : null;
    }

    protected function isModernRequest(mixed $message): bool
    {
        $meta = is_array($message) && is_array($message['params'] ?? null) ? ($message['params']['_meta'] ?? null) : null;

        return $this->request->getHeaders()->get('MCP-Protocol-Version') === Server::MODERN_PROTOCOL_VERSION
            || (is_array($meta) && isset($meta[Server::META_PROTOCOL_VERSION]));
    }

    /**
     * A modern request mirrors its method, tool and version into headers an intermediary may route on, so the two
     * must agree.
     */
    protected function validateHeaders(mixed $message, bool $isModern): ?JsonRpcException
    {
        $headers = $this->request->getHeaders();
        $version = $headers->get('MCP-Protocol-Version');

        if ($version !== null && !in_array($version, Server::getSupportedVersions(), true)) {
            return new JsonRpcException(JsonRpcException::UNSUPPORTED_PROTOCOL_VERSION, 'Unsupported protocol version', [
                'supported' => Server::getSupportedVersions(),
                'requested' => $version,
            ]);
        }

        if (!$isModern || !is_array($message) || !array_key_exists('id', $message)) {
            return null;
        }

        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
        $meta = is_array($params['_meta'] ?? null) ? $params['_meta'] : [];

        $expected = [
            'MCP-Protocol-Version' => $meta[Server::META_PROTOCOL_VERSION] ?? null,
            'Mcp-Method' => $message['method'] ?? null,
        ];

        if (in_array($message['method'] ?? null, ['tools/call', 'resources/read', 'prompts/get'], true)) {
            $expected['Mcp-Name'] = $params['name'] ?? $params['uri'] ?? null;
        }

        foreach ($expected as $name => $value) {
            $header = $this->decodeHeaderValue($headers->get($name));

            if ($header === null || $header !== $value) {
                return new JsonRpcException(JsonRpcException::HEADER_MISMATCH, "Header mismatch: $name");
            }
        }

        return null;
    }

    protected function decodeHeaderValue(?string $value): ?string
    {
        if ($value !== null && preg_match('/^=\?base64\?(.*)\?=$/', $value, $matches)) {
            $decoded = base64_decode($matches[1], true);
            return $decoded === false ? null : $decoded;
        }

        return $value;
    }

    protected function getStatusCode(mixed $code, bool $isModern): int
    {
        return match (true) {
            $code === null => 200,
            in_array($code, [JsonRpcException::PARSE_ERROR, JsonRpcException::INVALID_REQUEST], true) => 400,
            !$isModern => 200,
            $code === JsonRpcException::METHOD_NOT_FOUND => 404,
            $code === JsonRpcException::INTERNAL_ERROR => 500,
            default => 400,
        };
    }

    /**
     * @param array<string, mixed>|JsonRpcException|null $body
     */
    protected function respond(int $status, array|JsonRpcException|null $body = null, mixed $message = null): Response
    {
        $response = $this->response;
        $response->format = Response::FORMAT_RAW;
        $response->setStatusCode($status);

        if ($body instanceof JsonRpcException) {
            $id = is_array($message) ? ($message['id'] ?? null) : null;
            $body = array_filter([
                'jsonrpc' => '2.0',
                'id' => is_string($id) || is_int($id) ? $id : null,
                'error' => $body->toArray(),
            ], fn (mixed $value): bool => $value !== null);
        }

        if ($body !== null) {
            $response->getHeaders()->set('Content-Type', 'application/json');
            $response->content = Server::encode($body);
        }

        return $response;
    }
}
