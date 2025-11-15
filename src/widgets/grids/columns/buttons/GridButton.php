<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use yii\db\ActiveRecord;

abstract class GridButton extends Widget
{
    use ContainerConfigurationTrait;
    use TagIconTextTrait;

    protected ?string $label = null;
    protected ?ActiveRecord $model = null;
    protected array|null $href = null;

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

    public function href(array|null $href): static
    {
        $this->href = $href;
        return $this;
    }
}
