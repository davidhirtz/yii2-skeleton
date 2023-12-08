<?php

namespace davidhirtz\yii2\skeleton\behaviors\stubs;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;

trait TrailBehaviorTrait
{
    public function getTrailModelName(): string
    {
        return '';
    }

    /**
     * @see TrailBehavior::getTrailParents()
     */
    public function getTrailParents(): array
    {
        return [];
    }

    public function getTrailModelAdminRoute(): array|false
    {
        return false;
    }
}