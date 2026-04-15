<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use yii\base\Model;

/**
 * @template T of Model|null
 */
trait ModelTrait
{
    /**
     * @var T
     */
    public ?Model $model = null;

    /**
     * @param T $model
     */
    public function model(?Model $model): static
    {
        $this->model = $model;
        return $this;
    }
}
