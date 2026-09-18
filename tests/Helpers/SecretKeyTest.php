<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class SecretKeyTest extends TestCase
{
    public function testTheKeyFallsBackToTheCookieValidationKey(): void
    {
        self::assertSame('test', SecretKey::get());

        Yii::$app->params['secretKey'] = 'secret';
        self::assertSame('secret', SecretKey::get());
    }

    public function testAGeneratedKeyIsUrlSafeAndOfTheAskedLength(): void
    {
        foreach ([32, 40, 64] as $length) {
            $key = SecretKey::generate($length);

            self::assertSame($length, strlen($key));
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key);
        }

        self::assertNotSame(SecretKey::generate(), SecretKey::generate());
    }
}
