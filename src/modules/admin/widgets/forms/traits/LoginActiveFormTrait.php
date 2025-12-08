<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\forms\traits;

use Hirtz\Skeleton\html\Icon;
use Hirtz\Skeleton\widgets\forms\fields\InputField;
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
