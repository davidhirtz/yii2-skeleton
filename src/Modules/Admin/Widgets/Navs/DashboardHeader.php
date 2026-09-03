<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
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
            ? Lang::t('skeleton', 'DASHBOARD_HEADER_HELLO_GOOD', $params)
            : Lang::t('skeleton', 'DASHBOARD_HEADER_WELCOME_NICE', $params);

        $lastLogin = Yii::$app->getSession()->get('last_login_timestamp');

        if ($lastLogin) {
            $this->subheading ??= Lang::t('skeleton', 'DASHBOARD_HEADER_LAST_LOGIN', [
                'timestamp' => RelativeTime::make()->value($lastLogin),
            ]);
        }

        parent::configure();
    }
}
