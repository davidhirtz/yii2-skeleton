<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\Forms\AccountCredentialsForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\DbSession;
use Yii;
use yii\db\Query;

class UserSessionTest extends TestCase
{
    use UserFixtureTrait;

    public function testDestroyOtherSessionsKeepsTheCurrentOne(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->insertSession('other-device', $user->id);
        $this->insertSession('another-device', $user->id);
        $this->insertSession('someone-else', $this->getUserFromFixture('admin')->id);

        self::assertSame(2, $this->getWebUser()->destroyOtherSessions($user));

        self::assertFalse($this->hasSession('other-device'));
        self::assertFalse($this->hasSession('another-device'));
        self::assertTrue($this->hasSession('someone-else'));
    }

    public function testPasswordChangeEndsOtherSessions(): void
    {
        $user = $this->getUserFromFixture('owner');
        $this->insertSession('other-device', $user->id);

        $form = AccountCredentialsForm::create(['user' => $user]);
        $form->oldPassword = 'password';
        $form->newPassword = 'new-password';
        $form->repeatPassword = 'new-password';

        self::assertTrue($form->save());
        self::assertFalse($this->hasSession('other-device'));
    }

    private function getSession(): DbSession
    {
        $session = $this->getWebSession();
        self::assertInstanceOf(DbSession::class, $session);

        return $session;
    }

    private function insertSession(string $id, int $userId): void
    {
        Yii::$app->getDb()->createCommand()
            ->insert($this->getSession()->sessionTable, [
                'id' => $id,
                'user_id' => $userId,
                'expire' => time() + 3600,
            ])
            ->execute();
    }

    private function hasSession(string $id): bool
    {
        return (new Query())
            ->from($this->getSession()->sessionTable)
            ->where(['id' => $id])
            ->exists();
    }
}
