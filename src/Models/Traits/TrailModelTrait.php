<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Interfaces\AdminRouteInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use ReflectionClass;

trait TrailModelTrait
{
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed
    {
        $definition = $this instanceof CustomAttributeInterface ? $this->getCustomAttribute($attribute) : null;

        return $definition
            ? $definition->formatValue($this, $value)
            : TrailModelCollection::formatAttributeValue($this, $attribute, $value);
    }

    public function getTrailAttributes(): array
    {
        $exclude = $this->getTrailBehavior()->exclude;

        if ($this instanceof ActiveRecord && $this instanceof CustomAttributeInterface) {
            $exclude[] = $this->getCustomAttributesColumn();
        }

        return array_diff($this->attributes(), $exclude);
    }

    public function getTrailBehavior(): TrailBehavior
    {
        /** @var TrailBehavior $behavior */
        $behavior = $this->getBehavior('TrailBehavior');
        return $behavior;
    }

    public function getTrailModelAdminRoute(): array|false
    {
        return $this instanceof AdminRouteInterface ? $this->getAdminRoute() : false;
    }

    public function getTrailModelName(): string
    {
        return (new ReflectionClass(static::class))->getShortName();
    }

    public function getTrailModelType(): ?string
    {
        return null;
    }

    public function getTrailParents(): ?array
    {
        return null;
    }
}
