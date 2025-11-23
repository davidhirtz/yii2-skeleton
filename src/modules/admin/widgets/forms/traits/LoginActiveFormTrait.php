<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;

trait LoginActiveFormTrait
{
    protected function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('email')
            ->autocomplete('email')
            ->autofocus(!$this->model->hasErrors())
            ->prepend(Icon::make()
                ->name('envelope'))
            ->placeholder()
            ->type('email');
    }

    protected function getPasswordField(?string $autocomplete = null): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('password')
            ->prepend(Icon::make()
                ->name('key'))
            ->autocomplete($autocomplete)
            ->placeholder()
            ->type('password');
    }
}
