<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
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
 * @see AccountController::actionLogoutOtherSessions()
 */
class AccountLogoutOtherSessionsButton extends Widget
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
    protected function configure(): void
    {
        $this->icon ??= 'right-from-bracket';
        $this->label ??= Yii::t('skeleton', 'ACCOUNT_LOGOUT_OTHER_SESSIONS');
        $this->title ??= Yii::t('skeleton', 'ACCOUNT_LOGOUT_OTHER_SESSIONS');
        $this->url ??= ['/admin/account/logout-other-sessions'];

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
        return P::make()->text(Yii::t('skeleton', 'ACCOUNT_CONFIRM_LOGOUT_OTHER_SESSIONS'));
    }

    protected function getButton(): string|Stringable
    {
        return Button::make()
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
