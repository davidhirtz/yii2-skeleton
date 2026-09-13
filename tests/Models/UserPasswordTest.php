<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserPasswordTest extends TestCase
{
    use UserFixtureTrait;

    public function testHashWithoutAPepperRecordsNoScheme(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->generatePasswordHash('new-password');

        self::assertNull($user->password_scheme);
        self::assertTrue($user->validatePassword('new-password'));
        self::assertFalse($user->validatePassword('password'));
        self::assertFalse($user->isPasswordHashOutdated());
    }

    public function testPepperIsPartOfTheHash(): void
    {
        Yii::$app->params['passwordPepper'] = 'a-pepper-from-params';

        $user = $this->getUserFromFixture('owner');
        $user->generatePasswordHash('new-password');
        $hash = $user->password_hash;

        self::assertTrue($user->validatePassword('new-password'));

        // Without the pepper the same password no longer matches, which is what it buys against a leaked hash
        Yii::$app->params['passwordPepper'] = null;

        self::assertFalse($user->validatePassword('new-password'));
        self::assertEquals($hash, $user->password_hash);
    }

    public function testLoginRehashesAnOutdatedHashOnce(): void
    {
        $user = $this->getUserFromFixture('owner');
        $hash = $user->password_hash;

        // A hash written before the installation configured a pepper
        Yii::$app->params['passwordPepper'] = 'a-pepper-from-params';

        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => $user->email,
            'password' => 'password',
        ]);

        self::assertTrue($form->login());

        $user = User::findOne($user->id);

        self::assertEquals(User::PASSWORD_PEPPER, $user->password_scheme);
        self::assertNotEquals($hash, $user->password_hash);
        self::assertFalse($user->isPasswordHashOutdated());
        self::assertTrue($user->validatePassword('password'));
    }

    public function testAddingAPepperToARunningInstallationRehashesOnLogin(): void
    {
        $user = $this->getUserFromFixture('owner');

        // A hash already migrated off the legacy salt, written before anyone configured a pepper
        $user->generatePasswordHash('password');
        $user->update();

        self::assertNull($user->password_scheme);
        self::assertFalse($user->isPasswordHashOutdated());

        Yii::$app->params['passwordPepper'] = 'a-pepper-from-params';

        $user = User::findOne($user->id);

        // The old hash still validates, and is only marked for replacement
        self::assertTrue($user->validatePassword('password'));
        self::assertTrue($user->isPasswordHashOutdated());

        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => $user->email,
            'password' => 'password',
        ]);

        self::assertTrue($form->login());

        $user = User::findOne($user->id);

        self::assertEquals(User::PASSWORD_PEPPER, $user->password_scheme);
        self::assertFalse($user->isPasswordHashOutdated());
        self::assertTrue($user->validatePassword('password'));
    }

    public function testRemovingThePepperMarksTheHashOutdated(): void
    {
        Yii::$app->params['passwordPepper'] = 'a-pepper-from-params';

        $user = $this->getUserFromFixture('owner');
        $user->generatePasswordHash('password');

        self::assertEquals(User::PASSWORD_PEPPER, $user->password_scheme);
        self::assertFalse($user->isPasswordHashOutdated());

        Yii::$app->params['passwordPepper'] = null;

        self::assertTrue($user->isPasswordHashOutdated());
    }

    public function testPasswordIsCappedAtTheBcryptLimit(): void
    {
        self::assertSame(72, User::instance()->passwordMaxLength);
        self::assertSame(8, User::instance()->passwordMinLength);
    }
}
