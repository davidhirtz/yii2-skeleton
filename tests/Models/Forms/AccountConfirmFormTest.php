<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Forms\AccountConfirmForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountConfirmFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testConfirmWithValidToken(): void
    {
        $user = $this->getUserFromFixture('admin');
        self::assertTrue($user->isUnconfirmed());

        $form = $this->createForm($user->createVerificationToken());

        self::assertTrue($form->confirm());
        self::assertEquals($user->id, $form->user->id);

        $user = User::findOne($user->id);

        self::assertFalse($user->isUnconfirmed());
        self::assertEquals(0, $this->countTokens($user, UserToken::TYPE_VERIFICATION));
    }

    public function testConfirmWithWrongToken(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->createVerificationToken();

        $form = $this->createForm(str_repeat('a', 32));

        self::assertFalse($form->confirm());
        self::assertTrue(User::findOne($user->id)->isUnconfirmed());
    }

    public function testConfirmWithExpiredToken(): void
    {
        $user = $this->getUserFromFixture('admin');
        $token = $user->createVerificationToken();

        UserToken::updateAll(
            ['expires_at' => (new DateTime('-1 hour'))->format('Y-m-d H:i:s')],
            ['user_id' => $user->id]
        );

        $form = $this->createForm($token);

        self::assertFalse($form->confirm());
        self::assertArrayHasKey('code', $form->getErrors());
        self::assertTrue(User::findOne($user->id)->isUnconfirmed());
    }

    private function countTokens(User $user, string $type): int
    {
        return (int)UserToken::find()->whereUser($user->id)->whereType($type)->count();
    }

    private function createForm(string $code): AccountConfirmForm
    {
        return Yii::$container->get(AccountConfirmForm::class, [], [
            'code' => $code,
        ]);
    }
}
