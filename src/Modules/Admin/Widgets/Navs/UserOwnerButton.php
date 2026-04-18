<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Models\User;
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

class UserOwnerButton extends Widget
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

    public function isVisible(): bool
    {
        return $this->webuser->getIdentity()->isOwner() && !$this->model->isOwner();
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'star';
        $this->label ??= Yii::t('skeleton', 'Transfer Ownership');
        $this->title ??= Yii::t('skeleton', 'Transfer Ownership');
        $this->url ??= ['/admin/user/ownership', 'id' => $this->model->id];

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
        return P::make()->text(Yii::t('skeleton', 'Are you sure you want to transfer ownership of this account to another user? This will remove all your admin privileges and there is no going back. Please be certain!'));
    }

    protected function getButton(): string|Stringable
    {
        return Button::make()
            ->danger()
            ->icon($this->icon)
            ->modal($this->getModal())
            ->text(Yii::t('skeleton', 'Make Site Owner'));
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
