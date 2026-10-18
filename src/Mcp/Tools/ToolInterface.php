<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

interface ToolInterface
{
    public function getName(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    /**
     * @return array<string, mixed> a JSON Schema `object`, whose `required` keys and scalar `type`s the server checks
     * before {@see static::call()}
     */
    public function getInputSchema(): array;

    public function isReadOnly(): bool;

    public function isIdempotent(): bool;

    /**
     * Whether a call may overwrite or remove what is there, which a client takes as the reason to ask first.
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
