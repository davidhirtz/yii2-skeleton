<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class UserTest extends TestCase
{
    public function testSetUploadPath(): void
    {
        $model = User::create();
        $model->setUploadPath('/test');
        $expected = Yii::getAlias('@webroot/test/');

        self::assertEquals($expected, $model->uploadPath);
    }
}
