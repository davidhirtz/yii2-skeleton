<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Models\Forms\AccountCredentialsForm;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountCredentialsFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testUpdateEmailAddress(): void
    {
        $form = $this->createForm();

        $form->user->email = 'invalid_email';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));

        $form->user->email = 'owner@domain.com';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'ACCOUNT_UPDATE_CURRENT_PASSWORD');
        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'wrong';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'This email address is already used by another user.');
        self::assertEquals($expected, $form->getFirstError('email'));

        $form->user->email = 'valid@domain.com';

        self::assertTrue($form->save());
        self::assertNotEmpty($form->user->verification_token);

        $message = $this->mailer->getLastMessage();
        self::assertStringContainsString($form->user->getEmailConfirmationUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = $this->createForm();
        $form->user->passwordMinLength = strlen('new_password');

        $form->newPassword = 'short';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.', [
            'attribute' => $form->getAttributeLabel('newPassword'),
            'min' => $form->user->passwordMinLength,
        ]);

        self::assertEquals($expected, $form->getFirstError('newPassword'));

        $form->newPassword = 'new_password';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'ACCOUNT_UPDATE_CURRENT_PASSWORD');
        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('repeatPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'wrong_new_password';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'The password must match the new password.');
        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'new_password';
        self::assertTrue($form->save());

        $trail = Trail::find()->orderBy(['id' => SORT_DESC])->one();

        self::assertNull($trail->data);
        self::assertEquals($trail::TYPE_PASSWORD, $trail->type);
    }

    public function testLoadIgnoresAttributesOfOtherForms(): void
    {
        $form = $this->createForm();
        $name = $form->user->name;

        $form->load([
            $form->user->formName() => [
                'email' => 'valid@domain.com',
                'name' => 'renamed',
            ],
        ]);

        self::assertEquals('valid@domain.com', $form->user->email);
        self::assertEquals($name, $form->user->name);
    }

    protected function createForm(): AccountCredentialsForm
    {
        return AccountCredentialsForm::create([
            'user' => User::findOne(3),
        ]);
    }
}
