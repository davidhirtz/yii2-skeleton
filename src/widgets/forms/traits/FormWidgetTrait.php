<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\traits;

use Hirtz\Skeleton\widgets\forms\ActiveForm;

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
