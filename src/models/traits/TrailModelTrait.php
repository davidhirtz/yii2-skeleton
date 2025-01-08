<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\models\collections\TrailModelCollection;
use ReflectionClass;

trait TrailModelTrait
{
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed
    {
        return TrailModelCollection::formatAttributeValue($this, $attribute, $value);
    }

    /**
     * Returns the attributes that trigger a {@see Trail::TYPE_CREATE} or {@see Trail::TYPE_UPDATE} record. In the
     * default implementation this includes all attributes except attributes defined in {@see TrailBehavior::$exclude}.
     * This method can be overridden by the owner class to provide a more defined list of values which should be
     * logged.
     */
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

    /**
     * Class can override this method to provide a route to the admin route of the model
     */
    public function getTrailModelAdminRoute(): array|false
    {
        // @phpstan-ignore-next-line not sure why phpstan is complaining here...
        return method_exists($this, 'getAdminRoute') ? $this->getAdminRoute() : false;
    }

    /**
     * Class can override this method to provide a more detailed description of the model
     */
    public function getTrailModelName(): string
    {
        return (new ReflectionClass(static::class))->getShortName();
    }

    /**
     * Class can override this method to provide additional information about the model
     */
    public function getTrailModelType(): ?string
    {
        return null;
    }

    /**
     * Class can override this method to provide a real parent class
     */
    public function getTrailParents(): ?array
    {
        return null;
    }
}
