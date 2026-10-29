<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use BackedEnum;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\IntBackedEnumTrait;

/**
 * Never mutated after construction: what holds a definition caches it, and every use of its value shares the instance.
 */
abstract class Definition
{
    use ContainerConfigurationTrait;
    use IntBackedEnumTrait;

    protected ?string $name = null;
    protected ?string $icon = null;

    public readonly int $value;

    public function __construct(int|BackedEnum $value)
    {
        $this->value = static::getIntValue($value);
    }

    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    protected function getDisplayValue(): string
    {
        return static::class . ' "' . $this->value . '"';
    }
}
