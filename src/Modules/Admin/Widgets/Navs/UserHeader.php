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
        }

        if ($this->provider) {
            $this->title ??= Yii::t('skeleton', 'Users');
            $this->url ??= ['/admin/user/index'];
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
        }

        if (!$this->provider instanceof UserActiveDataProvider) {
            $this->breadcrumbs ??= [Yii::t('app', 'Users') => ['/admin/user/index']];
        }

        if (!$this->model) {
            $this->addCreateUserButton();
        }

        parent::configure();
    }

    protected function addCreateUserButton(): static
    {
        return $this->content(UserCreateButton::make());
    }
}
