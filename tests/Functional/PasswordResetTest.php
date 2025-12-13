<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class PasswordResetTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testPasswordResetWithMissingUrlParameters(): void
    {
        $this->open('admin/account/reset');
        self::assertResponseStatusCodeSame(400);
    }

    public function testPasswordResetWithInvalidUrlParameters(): void
    {
        $this->open('admin/account/reset', [
            'email' => 'invalid@domain.com',
            'code' => 'invalid-code',
        ]);

        self::assertCurrentUrlEquals('');
    }

    public function testPasswordResetWithWrongInputs(): void
    {
        $user = $this->getUser();
        $this->open($user->getPasswordResetUrl());

        self::submitPasswordResetForm('new-password', 'wrong-repeat-password');
        self::assertAnyValidationErrorSame('The password must match the new password.');
    }

    public function testPasswordResetWithCorrectInputs(): void
    {
        $user = $this->getUser();
        $this->open($user->getPasswordResetUrl());

        self::submitPasswordResetForm('new-password', 'new-password');
        self::assertCurrentUrlEquals('/');

        self::assertFalse(Yii::$app->getUser()->getIsGuest());
        self::assertEquals($user->id, Yii::$app->getUser()->getId());
    }

    private function getUser(): User
    {
        $user = User::findOne(1);
        $user->generatePasswordResetToken();
        $user->update();

        return $user;
    }

    private function submitPasswordResetForm(string $newPassword, string $repeatPassword): void
    {
        $form = self::$crawler->filter('form')->form(array_filter([
            Html::getInputName(PasswordResetForm::instance(), 'newPassword') => $newPassword,
            Html::getInputName(PasswordResetForm::instance(), 'repeatPassword') => $repeatPassword,
        ]));

        self::$crawler = self::$client->submit($form);
    }
}
