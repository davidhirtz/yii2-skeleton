<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Web\Application;
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
            ? Yii::t('skeleton', 'DASHBOARD_WELCOME_BACK_TITLE', $params)
            : Yii::t('skeleton', 'DASHBOARD_WELCOME_TITLE', $params);

        $lastLogin = Application::current()->getSession()->get('last_login_timestamp');

        if ($lastLogin) {
            $this->subheading ??= Div::make()
                ->class('hidden-sticky')
                ->content(Yii::t('skeleton', 'DASHBOARD_LAST_LOGIN_TEXT', [
                    'timestamp' => RelativeTime::make()->value($lastLogin),
                ]));
        }

        parent::configure();
    }
}
