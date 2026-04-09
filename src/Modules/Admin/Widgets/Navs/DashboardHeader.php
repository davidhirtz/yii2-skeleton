<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Yii;

class DashboardHeader extends Header
{
    protected User $webuser;

    public function __construct(array $config = [])
    {
        $this->webuser = Yii::$app->getUser();
        $identity = $this->webuser->getIdentity();
        $params = ['user' => $identity->getUsername()];

        $this->title ??= $identity->login_count > 1
            ? Yii::t('skeleton', 'Hello {user}, good to have you back!', $params)
            : Yii::t('skeleton', 'Welcome {user}, nice to meet you!', $params);

        parent::__construct($config);
    }
}
