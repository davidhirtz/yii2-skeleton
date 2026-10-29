<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Footers\UpdatedAtFooterItem;
use Hirtz\Skeleton\Widgets\Username;
use Override;
use Stringable;
use Yii;

/**
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    #[Override]
    protected function configure(): void
    {
        $this->readonly = !$this->webuser->can(User::AUTH_USER, ['user' => $this->model->user]);

        $this->submitButtonText ??= $this->model->user->getIsNewRecord()
            ? Yii::t('skeleton', 'FORM_CREATE_BUTTON')
            : Yii::t('skeleton', 'FORM_UPDATE_BUTTON');

        $this->footer ??= [
            $this->getUpdatedAtFooterItem(),
            $this->getCreatedAtFooterItem(),
        ];

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            [
                $this->getStatusField(),
                $this->getNameField(),
                $this->getEmailField(),
                $this->getNewPasswordField(),
                $this->getRepeatPasswordField(),
            ],
            [
                $this->getLanguageField(),
                $this->getTimezoneField(),
                $this->getColorSchemeField(),
            ],
            [
                ...$this->getUserCustomAttributeFields(),
            ],
            [
                $this->getShowHintsField(),
            ],
            [
                $this->sendEmailField(),
            ],
        ];
    }

    protected function getUpdatedAtFooterItem(): Stringable
    {
        return UpdatedAtFooterItem::make()
            ->model($this->model->user);
    }

    protected function getCreatedAtFooterItem(): ?Stringable
    {
        if ($this->model->user->getIsNewRecord()) {
            return null;
        }

        $created = $this->model->user->created;

        $content = $created
            ? Yii::t('skeleton', 'USER_CREATED_BY_TEXT', [
                'timestamp' => RelativeTime::make()->value($this->model->user->created_at),
                'user' => Username::make()
                    ->user($created)
                    ->clickable(),
            ])
            : Yii::t('skeleton', 'USER_SIGNED_UP_TEXT', [
                'timestamp' => RelativeTime::make()->value($this->model->user->created_at),
            ]);

        return Li::make()
            ->class('form-footer-item')
            ->content($content);
    }

    protected function sendEmailField(): ?Stringable
    {
        return $this->model->isPasswordResetEnabled()
            ? CheckboxField::make()->property('sendEmail')
            : null;
    }
}
