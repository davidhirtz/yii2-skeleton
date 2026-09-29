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
    protected ?Model $model = null;

    /**
     * @param T $model
     */
    public function model(?Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return T
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }
}
