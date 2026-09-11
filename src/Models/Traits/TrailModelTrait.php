<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use ReflectionClass;

trait TrailModelTrait
{
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed
    {
        return TrailModelCollection::formatAttributeValue($this, $attribute, $value);
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), $this->getTrailBehavior()->exclude);
    }

    public function getTrailBehavior(): TrailBehavior
    {
        /** @var TrailBehavior $behavior */
        $behavior = $this->getBehavior('TrailBehavior');
        return $behavior;
    }

    public function getTrailModelAdminRoute(): array|false
    {
        return method_exists($this, 'getAdminRoute') ? $this->getAdminRoute() : false;
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
