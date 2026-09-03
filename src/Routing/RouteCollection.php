<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, Route>
 */
class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @var array<string, Route>
     */
    private array $names = [];

    /**
     * Routes are cloned on the way in, so the registry cannot be changed through a reference the caller
     * kept. Registering an identical route twice is a no-op: a bundle `Bootstrap` runs more than once
     * when it is registered both via `extra.bootstrap` and an application's `bootstrap` config.
     *
     * @return list<Route> the newly registered routes
     */
    public function add(Route ...$routes): array
    {
        $added = [];

        foreach ($routes as $route) {
            $route->validate();
            $stored = clone $route;

            if ($stored->name !== null) {
                $existing = $this->names[$stored->name] ?? null;

                if ($existing?->equals($stored)) {
                    continue;
                }

                if ($existing) {
                    throw new InvalidArgumentException(
                        sprintf('Route name "%s" is already taken by "%s".', $stored->name, $existing->action)
                    );
                }

                $this->names[$stored->name] = $stored;
            } elseif ($this->contains($stored)) {
                continue;
            }

            $this->routes[] = $stored;
            $added[] = $stored;
        }

        return $added;
    }

    public function contains(Route $route): bool
    {
        foreach ($this->routes as $existing) {
            if ($existing->equals($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Route> ordered by position, stable within a position
     */
    public function all(): array
    {
        $routes = $this->routes;
        usort($routes, static fn (Route $a, Route $b): int => $a->position <=> $b->position);

        return $routes;
    }

    public function getByName(string $name): ?Route
    {
        return $this->names[$name] ?? null;
    }

    public function hasName(string $name): bool
    {
        return isset($this->names[$name]);
    }

    #[Override]
    public function count(): int
    {
        return count($this->routes);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}
