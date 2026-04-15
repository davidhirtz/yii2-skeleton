<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use Closure;

trait EvaluateClosureTrait
{
    /**
     * @template T
     * @param array<Closure(T): T>|Closure(T): T|null $closure
     * @param T $value
     * @return T
     */
    protected function evaluate(array|Closure|null $closure, mixed $value = null): mixed
    {
        if ($closure === null) {
            return $value;
        }

        return is_array($closure)
            ? array_reduce($closure, fn ($carry, $pipe) => $pipe($carry), $value)
            : $closure($value);
    }
}
