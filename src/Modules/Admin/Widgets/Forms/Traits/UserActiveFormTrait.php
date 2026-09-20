<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TimezoneSelectField;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Stringable;
use Yii;

trait UserActiveFormTrait
{
    use CustomAttributeFieldsTrait;
    use ModuleTrait;

    /**
     * @return list<Field>
     */
    protected function getUserCustomAttributeFields(): array
    {
        return $this->getCustomAttributeFields($this->model->user);
    }

    protected function getStatusField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model)
            ->property('status')
            ->items(array_map(
                static fn (Status $status): string => $status->getName(),
                $this->model->user::getStatusDefinitions(),
            ))
            ->visible(!$this->model->user->isOwner());
    }

    protected function getNameField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('name');
    }

    protected function getEmailField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('email')
            ->type('email');
    }

    protected function getNewPasswordField(): string|Stringable
    {
        return InputField::make()
            ->property('newPassword')
            ->type('password');
    }

    protected function getRepeatPasswordField(): string|Stringable
    {
        return InputField::make()
            ->property('repeatPassword')
            ->type('password');
    }

    protected function getLanguageField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('language')
            ->visible(count(static::getModule()->getLanguages()) > 1);
    }

    protected function getTimezoneField(): string|Stringable
    {
        return TimezoneSelectField::make()
            ->model($this->model->user)
            ->property('timezone');
    }

    /**
     * The empty option is the third state: no scheme pinned, so the browser's own decides.
     */
    protected function getColorSchemeField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('color_scheme')
            ->prompt(Yii::t('skeleton', 'USER_COLOR_SCHEME_AUTO'));
    }

    /**
     * An unchecked checkbox posts nothing, so the stored `1` would survive the save without the hidden input.
     */
    protected function getShowHintsField(): string|Stringable
    {
        return CheckboxField::make()
            ->model($this->model->user)
            ->property('show_hints')
            ->uncheckedValue('0');
    }
}
