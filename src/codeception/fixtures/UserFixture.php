<?php

namespace davidhirtz\yii2\skeleton\codeception\fixtures;

use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;

    public function unload(): void
    {
        // Logout user before unloading fixture to prevent MySQL constraint error
        Yii::$app->getUser()->logout();

        parent::unload();
    }
}
