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

    /**
     * @return list<string>
     */
    public function getTrailAttributes(): array;

    public function getTrailBehavior(): TrailBehavior;

    /**
     * @return list<TrailModelInterface>|null
     */
    public function getTrailParents(): ?array;
}
