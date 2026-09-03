<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Stringable;
use Yii;

class RedirectActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Redirect>
     */
    use ModelTrait;

    #[Configure]
    protected function configure(): void
    {
        $this->addItem($this->getCreateButton(), $this->getDeleteButton());
        parent::configure();
    }

    protected function getCreateButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Lang::t('skeleton', 'COMMON_NEW_REDIRECT'));
    }

    protected function getDeleteButton(): ?Stringable
    {
        return DeleteButton::make()
            ->label(Lang::t('skeleton', 'REDIRECT_ACTION_DROPDOWN_DELETE_REDIRECT'))
            ->model($this->model);
    }
}
