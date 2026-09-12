<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class UserDeleteTest extends TestCase
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

    public function testDeleteButtonRendersTheEmailConfirmation(): void
    {
        $this->open("admin/user/update?id={$this->user->id}");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists($this->getDeleteFormSelector() . ' input[name="value"]');
    }

    public function testDeleteWithWrongEmail(): void
    {
        $this->postDeleteForm('wrong@domain.com');

        self::assertAlertSame(Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->user->getAttributeLabel('email'),
        ]), 'danger');

        self::assertNotNull(User::findOne($this->user->id));
    }

    public function testDeleteWithCorrectEmail(): void
    {
        $this->postDeleteForm($this->user->email);
        self::assertNull(User::findOne($this->user->id));
    }

    /**
     * The delete button posts through htmx, so its form carries no `action` for the browser to follow.
     */
    protected function postDeleteForm(string $value): void
    {
        $request = Yii::$app->getRequest();

        self::$crawler = self::$client->request('POST', "https://www.test.localhost/admin/user/delete?id={$this->user->id}", [
            'value' => $value,
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    protected function getDeleteFormSelector(): string
    {
        return 'form[hx-post^="/admin/user/delete"]';
    }
}
