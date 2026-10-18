<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use stdClass;

final readonly class ToolResult
{
    /**
     * The content blocks follow the JSON text, such as an image.
     *
     * @param array<string, mixed> $structuredContent
     * @param list<array{type: string, data: string, mimeType: string}> $content
     */
    public function __construct(
        public array $structuredContent,
        public bool $isError = false,
        public array $content = [],
    ) {
    }

    /**
     * @param array<string, mixed> $structuredContent
     * @param list<array{type: string, data: string, mimeType: string}> $content
     */
    public static function success(array $structuredContent, array $content = []): self
    {
        return new self($structuredContent, content: $content);
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
                ...$this->content,
            ],
            'structuredContent' => $content,
            'isError' => $this->isError,
        ];
    }
}
