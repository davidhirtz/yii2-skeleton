<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\models\User;

class UserTest extends Unit
{
    public function testSetUploadPath(): void
    {
        $model = User::create();
        $model->setUploadPath('/test');
        self::assertEquals('./test/', $model->uploadPath);
    }
}
