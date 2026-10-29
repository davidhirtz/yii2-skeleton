<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

interface ToolInterface
{
    public function getName(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    /**
     * The server checks its `required` keys and scalar `type`s before {@see static::call()}.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;

    public function isReadOnly(): bool;

    public function isIdempotent(): bool;

    /**
     * A client takes a destructive tool as the reason to ask first.
     */
    public function isDestructive(): bool;

    /**
     * Whether a call reaches a service outside the site, such as an image generator.
     */
    public function isOpenWorld(): bool;

    /**
     * @param array<string, mixed> $arguments
     */
    public function call(array $arguments): ToolResult;
}
