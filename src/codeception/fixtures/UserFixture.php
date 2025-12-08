<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\codeception\fixtures;

use Hirtz\Skeleton\models\User;
use Yii;
use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;

    #[\Override]
    public function unload(): void
    {
        // Logout user before unloading fixture to prevent MySQL constraint error
        Yii::$app->getUser()->logout();

        parent::unload();
    }
}
