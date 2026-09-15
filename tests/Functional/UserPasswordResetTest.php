<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class UserPasswordResetTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private User $user;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->open('admin/account/login');
        $owner = $this->getUserFromFixture('owner');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $owner->email,
            'password' => 'password',
        ]));

        $this->user = $this->getUserFromFixture('disabled');
    }

    public function testButtonIsOfferedOnTheUserPage(): void
    {
        $this->open("admin/user/update?id={$this->user->id}");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('button[hx-post^="/admin/user/reset"]');
    }

    public function testResetEmailsTheLink(): void
    {
        $this->postReset();

        self::assertAlertSame(Yii::t('skeleton', 'USER_SUCCESS_SENT_PASSWORD_RESET', [
            'email' => $this->user->email,
        ]), 'success');

        $message = $this->mailer->getLastMessage();

        self::assertEquals($this->user->email, $this->mailer->getLastMessageTo());
        self::assertStringContainsString('/admin/account/reset', $this->mailer->getLastMessageBody());

        self::assertNotNull($this->user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));
    }

    public function testResetIsRefusedWhenPasswordResetIsDisabled(): void
    {
        $this->getWebUser()->enablePasswordReset = false;

        $this->postReset();

        self::assertResponseStatusCodeSame(403);
        self::assertFalse($this->mailer->hasMessages());
    }

    private function postReset(): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', "https://www.test.localhost/admin/user/reset?id={$this->user->id}", [
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }
}
