<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use stdClass;

final readonly class ToolResult
{
    /**
     * @param array<string, mixed> $structuredContent
     */
    public function __construct(
        public array $structuredContent,
        public bool $isError = false,
    ) {
    }

    /**
     * @param array<string, mixed> $structuredContent
     */
    public static function success(array $structuredContent): self
    {
        return new self($structuredContent);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function error(string $message, array $data = []): self
    {
        return new self(['error' => $message, ...$data], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $content = $this->structuredContent ?: new stdClass();

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                ],
            ],
            'structuredContent' => $content,
            'isError' => $this->isError,
        ];
    }
}
