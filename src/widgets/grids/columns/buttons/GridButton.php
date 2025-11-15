<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use Stringable;
use Yii;
use yii\db\ActiveRecord;

abstract class GridButton implements Stringable
{
    protected ?string $icon = null;
    protected ?string $label = null;
    protected ?ActiveRecord $model = null;
    protected array|null $url = null;

    public function icon(string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function model(?ActiveRecord $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function url(array|null $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    final public static function make(): static
    {
        return Yii::$container->get(static::class);
    }

    abstract public function render(): string;
}
