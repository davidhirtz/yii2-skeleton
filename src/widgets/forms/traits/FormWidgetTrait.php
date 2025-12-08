<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Traits;

use Hirtz\Skeleton\Widgets\Forms\ActiveForm;

trait FormWidgetTrait
{
    protected ?ActiveForm $form = null;

    public function form(?ActiveForm $form): static
    {
        $this->form = $form;
        $this->model ??= $form?->model;

        return $this;
    }
}
