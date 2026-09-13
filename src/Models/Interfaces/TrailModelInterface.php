<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Behaviors\TrailBehavior;
use yii\base\Model;

/**
 * @mixin Model
 */
interface TrailModelInterface extends AdminModelInterface
{
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed;

    public function getTrailAttributes(): array;

    public function getTrailBehavior(): TrailBehavior;

    public function getTrailParents(): ?array;
}
