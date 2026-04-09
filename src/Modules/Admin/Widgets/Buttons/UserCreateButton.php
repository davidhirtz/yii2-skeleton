<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Yii;

class UserCreateButton extends CreateButton
{
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('skeleton', 'New User');
        $this->icon ??= 'user-plus';
        $this->roles ??= [User::AUTH_USER_CREATE];
        $this->url ??= ['/admin/user/create'];

        parent::__construct($config);
    }
}
