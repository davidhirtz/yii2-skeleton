<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Controllers;

use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Mcp\OAuth\TokenIssuer;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class OAuthController extends Controller
{
    public $enableCsrfValidation = false;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'authorization-server' => ['get'],
                    'protected-resource' => ['get'],
                    'token' => ['post'],
                ],
            ],
        ];
    }

    public function actionProtectedResource(): Response
    {
        $mcp = Mcp::getComponent();

        return $this->respond(200, [
            'resource' => $mcp->getResourceUrl(),
            'authorization_servers' => [$mcp->getIssuer()],
            'scopes_supported' => [OAuthGrant::SCOPE_READ, OAuthGrant::SCOPE_WRITE],
            'bearer_methods_supported' => ['header'],
            'resource_name' => Yii::$app->name,
        ], true);
    }

    /**
     * RFC 8414, with the two values a client needs to see before it identifies itself by a metadata document.
     */
    public function actionAuthorizationServer(): Response
    {
        return $this->respond(200, [
            'issuer' => Mcp::getComponent()->getIssuer(),
            'authorization_endpoint' => Url::to(['/admin/grant/authorize'], true),
            'token_endpoint' => Url::to(['/oauth/token'], true),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => [OAuthGrant::SCOPE_READ, OAuthGrant::SCOPE_WRITE, 'offline_access'],
            'client_id_metadata_document_supported' => true,
            'authorization_response_iss_parameter_supported' => true,
        ], true);
    }

    public function actionToken(): Response
    {
        $mcp = Mcp::getComponent();
        $cacheKey = [McpController::class, 'authentication-attempts', (string)$this->request->getUserIP()];

        if (CacheCounter::get($cacheKey) >= $mcp->authenticationAttemptLimit) {
            $this->response->getHeaders()->set('Retry-After', (string)$mcp->authenticationAttemptDuration);
            return $this->respond(429, ['error' => 'slow_down']);
        }

        try {
            $body = (new TokenIssuer($mcp))->handle($this->request->post(), $mcp->getResourceUrl());
        } catch (OAuthException $exception) {
            if ($exception->error === OAuthException::INVALID_GRANT) {
                CacheCounter::increment($cacheKey, $mcp->authenticationAttemptDuration);
            }

            return $this->respond($exception->getStatusCode(), $exception->toArray());
        }

        return $this->respond(200, $body);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function respond(int $status, array $body, bool $isPublic = false): Response
    {
        $response = $this->response;
        $response->format = Response::FORMAT_JSON;
        $response->setStatusCode($status);
        $response->data = $body;

        $headers = $response->getHeaders();

        if ($isPublic) {
            // Browser-based clients read the discovery documents from another origin
            $headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $headers->set('Cache-Control', 'no-store');
        }

        return $response;
    }
}
