<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm;
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
        $this->rows ??= [
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
            ],
            [
                $this->getFirstNameField(),
                $this->getLastNameField(),
                $this->getCityField(),
                $this->getCountryField(),
            ],
            [
                $this->sendEmailField(),
            ],
        ];

        $this->submitButtonText ??= $this->model->user->getIsNewRecord()
            ? Lang::t('skeleton', 'COMMON_CREATE')
            : Lang::t('skeleton', 'COMMON_UPDATE');

        $this->footer ??= [
            $this->getUpdatedAtFooterItem(),
            $this->getCreatedAtFooterItem(),
        ];

        parent::configure();
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
            ? Lang::t('skeleton', 'USER_ACTIVE_CREATED_BY', [
                'timestamp' => RelativeTime::make()->value($this->model->user->created_at),
                'user' => Username::make()
                    ->user($created)
                    ->clickable(),
            ])
            : Lang::t('skeleton', 'USER_ACTIVE_SIGNED_UP', [
                'timestamp' => RelativeTime::make()->value($this->model->user->created_at),
            ]);

        return Li::make()
            ->class('form-footer-item')
            ->content($content);
    }

    protected function sendEmailField(): ?Stringable
    {
        return CheckboxField::make()
            ->property('sendEmail');
    }
}
