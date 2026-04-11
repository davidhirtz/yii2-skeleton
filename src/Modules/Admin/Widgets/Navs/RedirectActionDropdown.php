<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Stringable;
use Yii;

class RedirectActionDropdown extends ActionDropdown
{
    protected Redirect $model;

    public function model(Redirect $model): static
    {
        $this->model = $model;
        return $this->addItem($this->getCreateButton(), $this->getDeleteButton());
    }

    protected function getCreateButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('skeleton', 'New Redirect'));
    }

    protected function getDeleteButton(): ?Stringable
    {
        return DeleteButton::make()
            ->label(Yii::t('skeleton', 'Delete Redirect'))
            ->model($this->model);
    }
}
