<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use BackedEnum;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\IntBackedEnumTrait;

/**
 * A named, iconed value the admin offers in a select. It is born with its value and must never be mutated
 * afterwards: what holds it caches it, and every use of that value shares the instance.
 *
 * {@see ModelDefinition} is the half a model declares and is validated against — a type, a status. A definition
 * that belongs to no record extends this one directly.
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
