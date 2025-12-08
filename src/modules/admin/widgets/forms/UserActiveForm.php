<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\forms;

use Hirtz\Skeleton\html\custom\RelativeTime;
use Hirtz\Skeleton\html\Li;
use Hirtz\Skeleton\modules\admin\models\forms\UserForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\traits\UserActiveFormTrait;
use Hirtz\Skeleton\widgets\forms\ActiveForm;
use Hirtz\Skeleton\widgets\forms\fields\CheckboxField;
use Hirtz\Skeleton\widgets\forms\footers\UpdatedAtFooterItem;
use Hirtz\Skeleton\widgets\Username;
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
            ? Yii::t('skeleton', 'Create')
            : Yii::t('skeleton', 'Update');

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
            ? Yii::t('skeleton', 'Created by {user} {timestamp}', [
                'timestamp' => RelativeTime::make()->value($this->model->user->created_at),
                'user' => Username::make()
                    ->user($created)
                    ->clickable(),
            ])
            : Yii::t('skeleton', 'Signed up {timestamp}', [
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
