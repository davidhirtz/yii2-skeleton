<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;

trait FormWidgetTrait
{
    protected ActiveForm $form;

    public function form(ActiveForm $form): static
    {
        $this->form = $form;
        return $this;
    }
}
