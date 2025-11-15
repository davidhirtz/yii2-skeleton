<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\widgets\panels\Panel;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\helpers\Json;

class UserPanel extends Widget
{
    use UserWidgetTrait;

    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->buttons(...$this->getButtons());
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
    protected function getDeletePictureButton(): ?Stringable
    {
        return $this->user->picture
            ? Button::make()
                ->primary()
                ->text(Yii::t('skeleton', 'Delete picture'))
                ->icon('portrait')
                ->post(['delete-picture', 'id' => $this->user->id])
            : null;
    }

    /**
     * @see UserController::actionDisableGoogleAuthenticator()
     */
    protected function getDisableGoogleAuthenticatorButton(): ?Stringable
    {
        return $this->user->google_2fa_secret
            ? Button::make()
                ->primary()
                ->text(Yii::t('skeleton', 'Disable 2FA'))
                ->icon('qrcode')
                ->post(['disable-google-authenticator', 'id' => $this->user->id])
            : null;
    }

    /**
     * @see UserController::actionReset()
     */
    protected function getCreatePasswordResetLinkButton(): Stringable
    {
        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Create password link'))
            ->text(Yii::t('skeleton', 'Are you sure you want to create a new password reset link? The current link will be invalidated.'))
            ->footer(Button::make()
                ->primary()
                ->text(Yii::t('skeleton', 'Create password link'))
                ->icon('key')
                ->post(['reset', 'id' => $this->user->id]));

        return Button::make()
            ->primary()
            ->text(Yii::t('skeleton', 'Create password link'))
            ->icon('key')
            ->modal($modal);
    }

    protected function getPasswordResetLinkButton(): ?Stringable
    {
        if (!$this->user->password_reset_token) {
            return null;
        }

        $url = $this->user->getPasswordResetUrl();

        $action = Button::make()
            ->primary()
            ->text(Yii::t('skeleton', 'Copy link'))
            ->icon('clipboard')
            ->attribute('onclick', 'navigator.clipboard.writeText(' . Json::htmlEncode($url) . ')');

        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Password reset link'))
            ->content(Html::tag('div', $url, ['class' => 'text-break']))
            ->footer($action);

        return Button::make()
            ->primary()
            ->text(Yii::t('skeleton', 'Show password link'))
            ->icon('clipboard')
            ->modal($modal);
    }
}
