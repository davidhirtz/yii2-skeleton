<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Exception;

class JsonRpcException extends Exception
{
    public const int PARSE_ERROR = -32700;
    public const int INVALID_REQUEST = -32600;
    public const int METHOD_NOT_FOUND = -32601;
    public const int INVALID_PARAMS = -32602;
    public const int INTERNAL_ERROR = -32603;
    public const int HEADER_MISMATCH = -32020;
    public const int UNSUPPORTED_PROTOCOL_VERSION = -32022;

    /**
     * @param array<string, mixed>|null $data
     */
    public function __construct(int $code, string $message, public readonly ?array $data = null)
    {
        parent::__construct($message, $code);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'data' => $this->data,
        ], fn (mixed $value): bool => $value !== null);
    }
}
