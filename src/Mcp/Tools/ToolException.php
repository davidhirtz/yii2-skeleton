<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Exception;

/**
 * A failure the caller can act on, answered as a tool result with `isError` rather than a JSON-RPC error.
 */
class ToolException extends Exception
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message, public readonly array $data = [])
    {
        parent::__construct($message);
    }
}
