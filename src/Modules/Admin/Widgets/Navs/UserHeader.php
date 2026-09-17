<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\UserCreateButton;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * @extends ModelHeader<User|null>
 */
class UserHeader extends ModelHeader
{
    /**
     * @use ProviderTrait<ActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            $this->title ??= $this->model->getUsername();
            $this->addContent($this->getUserActionDropdown());
        }

        if ($this->provider) {
            $this->title ??= Yii::t('skeleton', 'COMMON_USERS');
            $this->url ??= ['/admin/user/index'];
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
        }

        if ($this->provider instanceof UserActiveDataProvider) {
            $this->addCreateUserButton();
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
