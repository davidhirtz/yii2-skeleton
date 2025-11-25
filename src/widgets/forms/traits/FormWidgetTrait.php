<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;

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
