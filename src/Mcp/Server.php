<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Hirtz\Skeleton\Mcp\Tools\ToolCollection;
use Hirtz\Skeleton\Mcp\Tools\ToolInterface;
use Hirtz\Skeleton\Mcp\Tools\ToolResult;
use Hirtz\Skeleton\Web\User as WebUser;
use JsonException;
use stdClass;
use Throwable;
use Yii;
use yii\db\Exception as DbException;

/**
 * Serves both eras of the protocol: a request naming its version in `_meta` (2026-07-28) is answered statelessly,
 * one without is answered as the `initialize` revisions expect. Neither keeps state between messages.
 */
class Server
{
    final public const string MODERN_PROTOCOL_VERSION = '2026-07-28';

    /**
     * @var list<string> newest first
     */
    final public const array LEGACY_PROTOCOL_VERSIONS = ['2025-11-25', '2025-06-18'];

    final public const string META_PROTOCOL_VERSION = 'io.modelcontextprotocol/protocolVersion';
    final public const string META_CLIENT_CAPABILITIES = 'io.modelcontextprotocol/clientCapabilities';
    final public const string META_SERVER_INFO = 'io.modelcontextprotocol/serverInfo';

    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    public function __construct(
        public readonly ToolCollection $tools,
        protected readonly string $name,
        protected readonly string $version,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function getSupportedVersions(): array
    {
        return [self::MODERN_PROTOCOL_VERSION, ...self::LEGACY_PROTOCOL_VERSIONS];
    }

    public function handleJson(string $json): ?string
    {
        try {
            $response = $this->handle(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            $response = $this->createErrorResponse(null, new JsonRpcException(JsonRpcException::PARSE_ERROR, 'Parse error'));
        }

        return $response === null ? null : self::encode($response);
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function encode(array $response): string
    {
        return json_encode($response, self::JSON_FLAGS);
    }

    /**
     * @return array<string, mixed>|null `null` for a notification, which is never answered
     */
    public function handle(mixed $message): ?array
    {
        $id = is_array($message) && (is_string($message['id'] ?? null) || is_int($message['id'] ?? null))
            ? $message['id']
            : null;

        try {
            $request = $this->validateMessage($message);

            if (!array_key_exists('id', $request)) {
                return null;
            }

            /** @var string $method */
            $method = $request['method'];

            /** @var array<string, mixed> $params */
            $params = $request['params'] ?? [];

            $version = $this->getRequestedVersion($method, $params);
            $result = $this->dispatch($method, $params);

            if ($version === self::MODERN_PROTOCOL_VERSION) {
                $result = [
                    'resultType' => 'complete',
                    ...$result,
                    '_meta' => [self::META_SERVER_INFO => $this->getServerInfo()],
                ];
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result ?: new stdClass(),
            ];
        } catch (JsonRpcException $exception) {
            return $this->createErrorResponse($id, $exception);
        } catch (Throwable $exception) {
            Yii::error($exception, __METHOD__);
            return $this->createErrorResponse($id, new JsonRpcException(JsonRpcException::INTERNAL_ERROR, 'Internal error'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateMessage(mixed $message): array
    {
        if (!is_array($message) || array_is_list($message)) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Invalid request: batches are not supported');
        }

        if (($message['jsonrpc'] ?? null) !== '2.0' || !is_string($message['method'] ?? null)) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Invalid request');
        }

        if (array_key_exists('id', $message) && !is_string($message['id']) && !is_int($message['id'])) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Invalid request: id must be a string or an integer');
        }

        if (isset($message['params']) && (!is_array($message['params']) || ($message['params'] && array_is_list($message['params'])))) {
            throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, 'Invalid params: params must be an object');
        }

        /** @var array<string, mixed> $message */
        return $message;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getRequestedVersion(string $method, array $params): ?string
    {
        $meta = $params['_meta'] ?? null;
        $version = is_array($meta) ? ($meta[self::META_PROTOCOL_VERSION] ?? null) : null;

        if ($version === null || $method === 'initialize') {
            return null;
        }

        if (!is_string($version) || !in_array($version, self::getSupportedVersions(), true)) {
            throw new JsonRpcException(JsonRpcException::UNSUPPORTED_PROTOCOL_VERSION, 'Unsupported protocol version', [
                'supported' => self::getSupportedVersions(),
                'requested' => $version,
            ]);
        }

        if ($version === self::MODERN_PROTOCOL_VERSION && !is_array($meta[self::META_CLIENT_CAPABILITIES] ?? null)) {
            throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, 'Invalid params: _meta lacks ' . self::META_CLIENT_CAPABILITIES);
        }

        return $version;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function dispatch(string $method, array $params): array
    {
        return match ($method) {
            'initialize' => $this->initialize($params),
            'ping' => [],
            'server/discover' => $this->discover(),
            'tools/list' => ['tools' => array_map($this->describeTool(...), $this->tools->all())],
            'tools/call' => $this->callTool($params)->toArray(),
            default => throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND, "Method not found: $method"),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => in_array($requested, self::LEGACY_PROTOCOL_VERSIONS, true)
                ? $requested
                : self::LEGACY_PROTOCOL_VERSIONS[0],
            'capabilities' => $this->getCapabilities(),
            'serverInfo' => $this->getServerInfo(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function discover(): array
    {
        return [
            'supportedVersions' => self::getSupportedVersions(),
            'capabilities' => $this->getCapabilities(),
            '_meta' => [self::META_SERVER_INFO => $this->getServerInfo()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCapabilities(): array
    {
        return ['tools' => ['listChanged' => false]];
    }

    /**
     * @return array{name: string, version: string}
     */
    protected function getServerInfo(): array
    {
        return ['name' => $this->name, 'version' => $this->version];
    }

    /**
     * @return array<string, mixed>
     */
    protected function describeTool(ToolInterface $tool): array
    {
        return [
            'name' => $tool->getName(),
            'description' => $tool->getDescription(),
            'inputSchema' => $tool->getInputSchema(),
            'annotations' => [
                'readOnlyHint' => $tool->isReadOnly(),
                'destructiveHint' => false,
                'idempotentHint' => $tool->isIdempotent(),
                'openWorldHint' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function callTool(array $params): ToolResult
    {
        $name = $params['name'] ?? null;
        $tool = is_string($name) ? $this->tools->get($name) : null;

        if ($tool === null) {
            throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, 'Unknown tool: ' . (is_string($name) ? $name : ''));
        }

        $arguments = $params['arguments'] ?? [];

        if (!is_array($arguments) || ($arguments && array_is_list($arguments))) {
            throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, 'Invalid params: arguments must be an object');
        }

        /** @var array<string, mixed> $arguments */
        $this->log($tool, $arguments);

        if ($errors = $this->validateArguments($tool->getInputSchema(), $arguments)) {
            return ToolResult::error('Invalid arguments', ['errors' => $errors]);
        }

        try {
            return $this->runTool($tool, $arguments);
        } catch (Throwable $exception) {
            Yii::error($exception, __METHOD__);
            return ToolResult::error('The tool failed with an unexpected error.');
        }
    }

    /**
     * A long-running stdio process outlives the database connection; a call that lost it is run once more.
     *
     * @param array<string, mixed> $arguments
     */
    protected function runTool(ToolInterface $tool, array $arguments): ToolResult
    {
        try {
            return $tool->call($arguments);
        } catch (DbException $exception) {
            if (!in_array($exception->errorInfo[1] ?? null, [2006, 2013], true)) {
                throw $exception;
            }

            Yii::$app->getDb()->close();
            return $tool->call($arguments);
        }
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $arguments
     * @return array<string, string>
     */
    protected function validateArguments(array $schema, array $arguments): array
    {
        $errors = [];

        /** @var list<string> $required */
        $required = $schema['required'] ?? [];

        foreach ($required as $name) {
            if (!array_key_exists($name, $arguments)) {
                $errors[$name] = "Missing required argument \"$name\".";
            }
        }

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'] ?? [];

        foreach ($arguments as $name => $value) {
            $type = $properties[$name]['type'] ?? null;

            if ($type !== null && !$this->isType($value, (array)$type)) {
                $errors[$name] = "Argument \"$name\" must be of type " . implode('|', (array)$type) . '.';
            }
        }

        return $errors;
    }

    /**
     * @param array<array-key, mixed> $types
     */
    protected function isType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            $matches = match ($type) {
                'string' => is_string($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'boolean' => is_bool($value),
                'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && (!$value || !array_is_list($value)),
                'null' => $value === null,
                default => true,
            };

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * The arguments may hold content, so only the record they name is logged.
     *
     * @param array<string, mixed> $arguments
     */
    protected function log(ToolInterface $tool, array $arguments): void
    {
        $parts = [$tool->getName()];

        foreach (['resource', 'id'] as $key) {
            if (is_string($arguments[$key] ?? null) || is_int($arguments[$key] ?? null)) {
                $parts[] = "$key={$arguments[$key]}";
            }
        }

        if ($userId = WebUser::current()?->getId()) {
            $parts[] = "user=$userId";
        }

        Yii::info(implode(' ', $parts), 'mcp');
    }

    /**
     * @return array<string, mixed>
     */
    protected function createErrorResponse(string|int|null $id, JsonRpcException $exception): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => $exception->toArray(),
        ];
    }
}
