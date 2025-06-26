<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use Yii;
use yii\helpers\Json;

class UserHelpPanel extends HelpPanel
{
    public ?User $user = null;

    #[\Override]
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

        return Button::primary(Yii::t('skeleton', 'Delete picture'))
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

        return Button::primary(Yii::t('skeleton', 'Disable 2FA'))
            ->icon('qrcode')
            ->post(['disable-google-authenticator', 'id' => $this->user->id])
            ->render();
    }

    /**
     * @see UserController::actionReset()
     */
    protected function getCreatePasswordResetLinkButton(): string
    {
        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Create password link'))
            ->text(Yii::t('skeleton', 'Are you sure you want to create a new password reset link? The current link will be invalidated.'))
            ->footer(Button::primary(Yii::t('skeleton', 'Create password link'))
                ->icon('key')
                ->post(['reset', 'id' => $this->user->id]));

        return Button::primary(Yii::t('skeleton', 'Create password link'))
            ->icon('key')
            ->modal($modal)
            ->render();
    }

    protected function getPasswordResetLinkButton(): string
    {
        if (!$this->user->password_reset_token) {
            return '';
        }

        $url = $this->user->getPasswordResetUrl();

        $action = Button::primary(Yii::t('skeleton', 'Copy link'))
            ->icon('clipboard')
            ->attribute('onclick', 'navigator.clipboard.writeText(' . Json::htmlEncode($url) . ')');

        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Password reset link'))
            ->html(Html::tag('div', $url, ['class' => 'text-break']))
            ->footer($action);

        return Button::primary(Yii::t('skeleton', 'Show password link'))
            ->icon('clipboard')
            ->modal($modal)
            ->render();
    }
}
