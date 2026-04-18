<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\UserCreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * @property User|null $model
 */
class UserHeader extends Header
{
    use ModelTrait;
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            $this->title ??= $this->model->getUsername();
            $this->url ??= ['/admin/user/update', 'id' => $this->model->id];
            $this->addContent($this->getUserActionDropdown());
        }

        if ($this->provider) {
            $this->title ??= Yii::t('skeleton', 'Users');
            $this->url ??= ['/admin/user/index'];
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
        }

        if ($this->provider instanceof UserActiveDataProvider) {
            $this->addCreateUserButton();
        } else {
            $this->breadcrumbs ??= [Yii::t('app', 'Users') => ['/admin/user/index']];
        }

        parent::configure();
    }

    protected function addCreateUserButton(): static
    {
        return $this->content(UserCreateButton::make());
    }

    protected function getUserActionDropdown(): ?Stringable
    {
        return UserActionDropdown::make()
            ->model($this->model);
    }
}
