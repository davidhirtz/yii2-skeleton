<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing;

use InvalidArgumentException;

/**
 * Generates URLs from {@see Route::$name}, so call sites never name a router-specific action such as
 * `cms/site/view`.
 */
interface UrlGeneratorInterface
{
    /**
     * @param array<string, mixed> $params
     * @throws InvalidArgumentException if the name is unknown or a placeholder has no value
     */
    public function generate(string $name, array $params = []): string;

    /**
     * @param array<string, mixed> $params
     * @throws InvalidArgumentException if the name is unknown or a placeholder has no value
     */
    public function generateAbsolute(string $name, array $params = [], ?string $scheme = null): string;

    /**
     * @param array<string, mixed> $params
     * @throws InvalidArgumentException if the name is unknown or a placeholder has no value
     */
    public function generateDraft(string $name, array $params = []): string;

    public function hasRoute(string $name): bool;
}
