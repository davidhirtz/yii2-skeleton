<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\html\Btn;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use Yii;
use Yiisoft\Json\Json;

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

        return Btn::primary(Yii::t('skeleton', 'Delete picture'))
            ->icon('portrait')
            ->post(['delete-picture', 'id' => $this->user->id])
            ->render();
    }

    /**
     * @see UserController::actionDisableGoogleAuthenticator()
     */
    protected function getDisableGoogleAuthenticatorButton(): string
    {
        if (!$this->user->google_2fa_secret) {
            return '';
        }

        return Btn::primary(Yii::t('skeleton', 'Disable 2FA'))
            ->icon('qrcode')
            ->post(['disable-google-authenticator', 'id' => $this->user->id])
            ->render();
    }

    /**
     * @see UserController::actionReset()
     */
    protected function getCreatePasswordResetLinkButton(): string
    {
        return Btn::primary(Yii::t('skeleton', 'Create password link'))
            ->icon('key')
            ->confirm($this->user->password_reset_token ? Yii::t('skeleton', 'Are you sure you want to create a new password reset link? The current link will be invalidated.') : null)
            ->post(['reset', 'id' => $this->user->id])
            ->render();
    }

    protected function getPasswordResetLinkButton(): string
    {
        if (!$this->user->password_reset_token) {
            return '';
        }

        $url = $this->user->getPasswordResetUrl();
        $id = 'password-reset-link';

        $action = Btn::primary(Yii::t('skeleton', 'Copy link'))
            ->attribute('onclick', 'navigator.clipboard.writeText(' . Json::htmlEncode($url) . ')');

        $modal = Modal::tag()
            ->id($id)
            ->title(Yii::t('skeleton', 'Password reset link'))
            ->text($url, ['class' => 'text-break'])
            ->action($action)
            ->render();

        $btn = Btn::primary(Yii::t('skeleton', 'Show password link'))
            ->icon('clipboard')
            ->modal($id)
            ->render();

        return $modal . $btn;
    }
}
