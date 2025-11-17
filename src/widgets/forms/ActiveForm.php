<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Form;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class ActiveForm extends Widget
{

    protected function renderContent(): string|Stringable
    {
        return Form::make()
            ->action($this->getAction())
    }
}