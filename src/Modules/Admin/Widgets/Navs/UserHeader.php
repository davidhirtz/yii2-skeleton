<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\UserCreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;

class UserHeader extends Header
{
    protected UserActiveDataProvider $provider;

    public function provider(UserActiveDataProvider $provider): static
    {
        $this->provider = $provider;
        return $this->pagination($provider);
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'Users');
        $this->url ??= ['/admin/user/index'];

        $this->addCreateUserButton();

        parent::configure();
    }

    protected function addCreateUserButton(): static
    {
        return $this->content(UserCreateButton::make());
    }
}
