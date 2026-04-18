<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;

class DashboardHeader extends Header
{
    #[Override]
    protected function configure(): void
    {
        $identity = $this->webuser->getIdentity();
        $params = ['user' => $identity->getUsername()];

        $this->title ??= $identity->login_count > 1
            ? Yii::t('skeleton', 'Hello {user}, good to have you back!', $params)
            : Yii::t('skeleton', 'Welcome {user}, nice to meet you!', $params);

        $lastLogin = Yii::$app->getSession()->get('last_login_timestamp');

        if ($lastLogin) {
            $this->subheading ??= Yii::t('skeleton', 'Last login {timestamp}', [
                'timestamp' => RelativeTime::make()->value($lastLogin),
            ]);
        }

        parent::configure();
    }
}
