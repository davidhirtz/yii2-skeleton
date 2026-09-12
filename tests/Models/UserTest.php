<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserTest extends TestCase
{
    use UserFixtureTrait;

    public function testCustomAttributesAreStoredAndTrailed(): void
    {
        Yii::$container->set(User::class, [
            'customAttributes' => [TextCustomAttribute::make('first_name')->max(50)],
        ]);

        $user = User::findOne(3);
        $user->setAttribute('first_name', 'Test');

        self::assertTrue($user->save(), implode(' ', $user->getErrorSummary(true)));
        self::assertSame('Test', User::findOne(3)->getAttribute('first_name'));

        $trail = Trail::find()->orderBy(['id' => SORT_DESC])->one();
        self::assertSame(['first_name' => [null, 'Test']], $trail->data);
    }

    public function testCustomAttributesAreSafeAndValidated(): void
    {
        Yii::$container->set(User::class, [
            'customAttributes' => [TextCustomAttribute::make('first_name')->max(50)],
        ]);

        $user = User::findOne(3);
        self::assertContains('first_name', $user->safeAttributes());

        $user->setAttribute('first_name', str_repeat('a', 51));

        self::assertFalse($user->validate());
        self::assertArrayHasKey('first_name', $user->getErrors());
    }

    public function testInitialsFallBackToTheUsername(): void
    {
        $user = User::create();
        $user->name = 'owner';

        self::assertSame('ow', $user->getInitials());
    }
}
