<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use Closure;

trait EvaluateClosureTrait
{
    /**
     * @template TValue
     * @param array<Closure(TValue): TValue>|Closure(TValue): TValue|null $closure
     * @param TValue $value
     * @return TValue
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
