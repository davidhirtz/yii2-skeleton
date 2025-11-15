<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use yii\db\ActiveRecord;

abstract class GridButton extends Widget
{
    use ContainerConfigurationTrait;

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
}
