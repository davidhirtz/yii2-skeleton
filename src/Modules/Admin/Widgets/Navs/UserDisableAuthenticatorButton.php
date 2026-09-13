<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @see UserController::actionDisableAuthenticator()
 */
class UserDisableAuthenticatorButton extends Widget
{
    use IconTrait;

    /**
     * @use ModelTrait<User>
     */
    use ModelTrait;

    use LabelTrait;
    use TagContentTrait;
    use TitleTrait;
    use UrlTrait;

    #[Override]
    public function isVisible(): bool
    {
        return $this->model->hasTwoFactorAuthentication()
            && $this->webuser->enableTwoFactorAuthentication
            && $this->webuser->can(User::AUTH_USER_UPDATE, ['user' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'shield-halved';
        $this->label ??= Yii::t('skeleton', 'USER_DISABLE_AUTHENTICATOR_LABEL');
        $this->title ??= Yii::t('skeleton', 'USER_DISABLE_AUTHENTICATOR_LABEL');
        $this->url ??= ['/admin/user/disable-authenticator', 'id' => $this->model->id];

        $this->addContent($this->getMessage());

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->isVisible() ? $this->getButton() : '';
    }

    protected function getMessage(): ?Stringable
    {
        return P::make()->text(Yii::t('skeleton', 'USER_CONFIRM_DISABLE_AUTHENTICATOR'));
    }

    protected function getButton(): string|Stringable
    {
        return Button::make()
            ->danger()
            ->icon($this->icon)
            ->modal($this->getModal())
            ->text($this->label);
    }

    protected function getModal(): Modal
    {
        $button = Button::make()
            ->danger()
            ->post($this->url, true)
            ->text($this->label);

        return Modal::make()
            ->title($this->title)
            ->content(...$this->content)
            ->footer($button);
    }
}
