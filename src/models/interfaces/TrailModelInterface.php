<?php

namespace davidhirtz\yii2\skeleton\models\interfaces;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use yii\base\Model;

/**
 * @mixin Model
 */
interface TrailModelInterface
{
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed;

    public function getTrailAttributes(): array;

    public function getTrailModelAdminRoute(): array|false;

    public function getTrailModelName(): string;

    public function getTrailModelType(): ?string;

    public function getTrailBehavior(): TrailBehavior;

    public function getTrailParents(): ?array;
}
