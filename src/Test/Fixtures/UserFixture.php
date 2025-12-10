<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Fixtures;

use Hirtz\Skeleton\Models\User;
use Yii;
use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;

    public function unload(): void
    {
        // Logout user before unloading fixture to prevent MySQL constraint error
        // Todo: still needed?
        Yii::$app->getUser()->logout();

        parent::unload();
    }
}