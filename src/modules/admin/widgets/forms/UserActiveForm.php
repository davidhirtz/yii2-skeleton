<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\footers\UpdatedAtFooterItem;
use davidhirtz\yii2\skeleton\widgets\Username;
use davidhirtz\yii2\timeago\Timeago;
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
                'sendEmail',
            ],
        ];

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
                'timestamp' => Timeago::tag($this->model->user->created_at),
                'user' => Username::make()
                    ->user($created)
                    ->clickable(),
            ])
            : Yii::t('skeleton', 'Signed up {timestamp}', [
                'timestamp' => Timeago::tag($this->model->user->created_at),
            ]);

        return Li::make()
            ->class('form-footer-item')
            ->content($content);
    }
}
