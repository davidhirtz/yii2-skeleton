<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Forms\AccountConfirmForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountConfirmFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testConfirmWithValidToken(): void
    {
        $user = $this->getUserFromFixture('admin');
        $form = $this->createForm($user, $user->verification_token);

        self::assertTrue($form->confirm());
        self::assertNull(User::findOne($user->id)->verification_token);
    }

    public function testConfirmWithWrongToken(): void
    {
        $user = $this->getUserFromFixture('admin');
        $form = $this->createForm($user, str_repeat('a', 32));

        self::assertFalse($form->confirm());
        self::assertNotNull(User::findOne($user->id)->verification_token);
    }

    public function testConfirmWithExpiredToken(): void
    {
        $user = $this->getUserFromFixture('admin');

        $user->verification_token_created_at = new DateTime('-2 days');
        $user->update();

        $form = $this->createForm($user, $user->verification_token);

        self::assertFalse($form->confirm());
        self::assertArrayHasKey('code', $form->getErrors());
        self::assertNotNull(User::findOne($user->id)->verification_token);
    }

    private function createForm(User $user, string $code): AccountConfirmForm
    {
        return Yii::$container->get(AccountConfirmForm::class, [], [
            'email' => $user->email,
            'code' => $code,
        ]);
    }
}
