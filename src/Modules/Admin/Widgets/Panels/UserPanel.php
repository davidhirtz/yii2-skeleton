<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Traits\UserWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
                ->text(Lang::t('skeleton', 'USER_DELETE_PICTURE'))
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
                ->text(Lang::t('skeleton', 'USER_DISABLE_2FA'))
                ->icon('qrcode')
                ->post(['disable-authenticator', 'id' => $this->user->id])
            : null;
    }

    /**
     * @see UserController::actionReset()
     */
    protected function getCreatePasswordResetLinkButton(): Stringable
    {
        $modal = Modal::make()
            ->title(Lang::t('skeleton', 'USER_CREATE_PASSWORD_LINK'))
            ->text(Lang::t('skeleton', 'USER_CONFIRM_CREATE_PASSWORD_LINK'))
            ->footer(Button::make()
                ->primary()
                ->text(Lang::t('skeleton', 'USER_CREATE_PASSWORD_LINK'))
                ->icon('key')
                ->post(['reset', 'id' => $this->user->id]));

        return Button::make()
            ->primary()
            ->text(Lang::t('skeleton', 'USER_CREATE_PASSWORD_LINK'))
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
            ->text(Lang::t('skeleton', 'USER_COPY_LINK'))
            ->icon('clipboard')
            ->attribute('onclick', 'navigator.clipboard.writeText(' . Json::htmlEncode($url) . ')');

        $modal = Modal::make()
            ->title(Lang::t('skeleton', 'USER_PASSWORD_RESET_LINK'))
            ->content(Html::tag('div', $url, ['class' => 'text-break']))
            ->footer($action);

        return Button::make()
            ->primary()
            ->text(Lang::t('skeleton', 'USER_SHOW_PASSWORD_LINK'))
            ->icon('clipboard')
            ->modal($modal);
    }
}
