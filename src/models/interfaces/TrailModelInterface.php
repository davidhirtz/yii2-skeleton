<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models\interfaces;

use Hirtz\Skeleton\behaviors\TrailBehavior;
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
