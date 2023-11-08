<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use Yii;

class UserHelpPanel extends HelpPanel
{
    public ?User $user = null;

    public function init(): void
    {
        $this->content ??= $this->renderButtonToolbar(array_filter($this->getButtons()));

        parent::init();
    }

    protected function getButtons(): array
    {
        return [
            $this->getDeletePictureButton(),
            $this->getDisableGoogleAuthenticatorButton(),
            $this->getCreatePasswordResetLinkButton(),
            $this->getPasswordResetLinkButton(),
        ];
    }


    /**
     * @see UserController::actionDeletePicture()
     */
    protected function getDeletePictureButton(): string
    {
        if (!$this->user->picture) {
            return '';
        }

        return Html::a(Html::iconText('portrait', Yii::t('skeleton', 'Delete picture')), ['delete-picture', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    /**
     * @see UserController::actionDisableGoogleAuthenticator()
     */
    protected function getDisableGoogleAuthenticatorButton(): string
    {
        if (!$this->user->google_2fa_secret) {
            return '';
        }

        return Html::a(Html::iconText('qrcode', Yii::t('skeleton', 'Disable 2FA')), ['disable-google-authenticator', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    /**
     * @see UserController::actionReset()
     */
    protected function getCreatePasswordResetLinkButton(): string
    {
        return Html::a(Html::iconText('key', Yii::t('skeleton', 'Create password link')), ['reset', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-confirm' => $this->user->password_reset_token ? Yii::t('skeleton', 'Are you sure you want to create a new password reset link? The current link will be invalidated.') : null,
            'data-method' => 'post',
        ]);
    }

    protected function getPasswordResetLinkButton(): string
    {
        if (!$this->user->password_reset_token) {
            return '';
        }

        return Html::button(Html::iconText('clipboard', Yii::t('skeleton', 'Show password link')), [
            'class' => 'btn btn-secondary',
            'data-confirm' => Html::tag('div', $this->user->getPasswordResetUrl(), ['class' => 'text-break']),
        ]);
    }
}
