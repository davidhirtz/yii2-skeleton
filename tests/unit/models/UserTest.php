<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models;

use Codeception\Test\Unit;
use Hirtz\Skeleton\models\User;

class UserTest extends Unit
{
    public function testSetUploadPath(): void
    {
        $model = User::create();
        $model->setUploadPath('/test');
        self::assertEquals('./test/', $model->uploadPath);
    }
}
