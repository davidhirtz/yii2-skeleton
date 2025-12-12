<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Auth\Clients\ClientInterface;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\AuthClient;
use Hirtz\Skeleton\Models\Forms\AuthClientSignupForm;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\authclient\BaseClient;

class AuthClientSignupFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testAuthClientWithDisabledLogin(): void
    {
        Yii::$app->getUser()->enableSignup = false;

        $form = $this->createAuthClientSignupForm();

        self::assertFalse($form->insert());
        self::assertEquals('Sorry, signing up is currently disabled!', $form->getFirstError('id'));
    }

    public function testAuthClientWithInvalidCredentials(): void
    {
        Yii::$app->getUser()->enableSignup = true;

        $form = $this->createAuthClientSignupForm();

        self::assertFalse($form->insert());
        self::assertEquals('Username cannot be blank.', $form->getFirstError('name'));
    }

    public function testAuthClientWithoutUsername(): void
    {
        Yii::$app->getUser()->enableSignup = true;

        $form = $this->createAuthClientSignupForm([
            'first_name' => ' Test ',
            'last_name' => '!!Client!!',
            'email' => 'auth-test-client@test.com',
        ]);

        self::assertTrue($form->insert());
        self::assertEquals('test-client', $form->user->name);

        $message = $this->mailer->getLastMessage();
        self::assertStringContainsString($form->user->getEmailConfirmationUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    public function testAuthClientWithExternalPicture(): void
    {
        Yii::setAlias('@webroot', '@runtime');
        Yii::$app->getUser()->enableSignup = true;

        $form = $this->createAuthClientSignupForm([
            'email' => 'auth-test-client@test.com',
        ]);

        $form->externalPictureUrl = Yii::getAlias('@skeleton/../resources/tests/data/test.png');

        self::assertTrue($form->insert());
        self::assertNotNull($form->user->picture);
        self::assertFileExists($form->user->getUploadPath() . $form->user->picture);

        FileHelper::removeDirectory(Yii::getAlias('@runtime/uploads'));
    }

    public function testAuthClientWithExistingUsername(): void
    {
        $user = $this->getUserFromFixture('admin');
        Yii::$app->getUser()->enableSignup = true;

        $form = $this->createAuthClientSignupForm([
            'name' => 'auth-test',
            'email' => $user->email,
        ]);

        self::assertFalse($form->insert());

        $expected = Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
            'client' => $form->client->getTitle(),
            'email' => $user->email,
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));
    }

    protected function createAuthClientSignupForm(array $attributes = []): AuthClientSignupForm
    {
        $client = new TestAuthClient();
        $client->userAttributes = $attributes;

        return AuthClientSignupForm::create(['client' => $client]);
    }
}

class TestAuthClient extends BaseClient implements ClientInterface
{
    public array $userAttributes = [];

    public function getAuthData(): array
    {
        return [];
    }

    protected function initUserAttributes(): array
    {
        return $this->userAttributes;
    }

    public function getSafeUserAttributes(): array
    {
        return $this->getUserAttributes();
    }

    #[Override]
    public function getViewOptions(): array
    {
        return [];
    }

    public static function getDisplayName(AuthClient $client): string
    {
        return 'Test-Display-Name';
    }

    public static function getExternalUrl(AuthClient $client): string
    {
        return 'https://fake-test-auth-url.com';
    }
}
