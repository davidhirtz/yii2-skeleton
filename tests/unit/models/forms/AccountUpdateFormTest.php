<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use DateTime;
use DateTimeZone;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Yii;
use yii\symfonymailer\Message;

class AccountUpdateFormTest extends Unit
{
    use UserFixtureTrait;

    public UnitTester $tester;

    public function testUpdateEmailAddress(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->email = 'invalid_email';
        self::assertFalse($form->update());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'owner@domain.com';
        self::assertFalse($form->update());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';

        self::assertFalse($form->update());

        $expected = Yii::t('skeleton', 'This email address is already used by another user.');
        self::assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'valid@domain.com';

        self::assertTrue($form->update());
        self::assertNotEmpty($form->user->verification_token);

        /** @var Message $message */
        $message = $this->tester->grabLastSentEmail();
        self::assertStringContainsString($form->user->getEmailConfirmationUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->user->passwordMinLength = strlen('new_password');

        $form->newPassword = 'short';
        self::assertFalse($form->update());

        $expected = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.', [
            'attribute' => $form->getAttributeLabel('newPassword'),
            'min' => $form->user->passwordMinLength,
        ]);

        self::assertEquals($expected, $form->getFirstError('newPassword'));

        $form->newPassword = 'new_password';
        self::assertFalse($form->update());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        self::assertFalse($form->update());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('repeatPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'wrong_new_password';
        $form->oldPassword = 'password';
        self::assertFalse($form->update());

        $expected = Yii::t('skeleton', 'The password must match the new password.');
        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'new_password';
        self::assertTrue($form->update());

        $trail = $this->getLastTrailRecord();

        self::assertNull($trail->data);
        self::assertEquals($trail::TYPE_PASSWORD, $trail->type);
    }

    public function testUpdateNameAttributes(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->name = '';
        self::assertFalse($form->update());

        $form->name = '\\!//';
        self::assertFalse($form->update());
        $expects = Yii::t('skeleton', 'Username must only contain alphanumeric characters.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'disabled';
        self::assertFalse($form->update());

        $expects = Yii::t('skeleton', 'This username is already used by another user.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'admin';
        $form->first_name = ' Test ';
        $form->last_name = 'User';

        self::assertTrue($form->update());
        self::assertEquals('Test', $form->first_name);
        self::assertEquals('Test User', $form->user->getFullName());
        self::assertEquals('TU', $form->user->getInitials());

        $trail = $this->getLastTrailRecord();

        $expects = [
            'first_name' => [null, 'Test'],
            'last_name' => [null, 'User'],
        ];

        self::assertEquals($expects, $trail->data);

        /** @var User $user */
        $user = $trail->getModelClass();
        self::assertEquals($user->getTrailModelName(), $form->user->getTrailModelName());
    }

    public function testUpdateTimezoneAttribute(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->timezone = 'invalid_timezone';
        self::assertFalse($form->update());

        $form->timezone = 'America/New_York';
        self::assertTrue($form->update());

        $dateTime = new DateTime('now', new DateTimeZone($form->timezone));
        self::assertEquals('GMT ' . $dateTime->format('P'), $form->user->getTimezoneOffset());

        $trail = $this->getLastTrailRecord();

        self::assertEquals(['timezone' => ['Europe/Berlin', $form->timezone]], $trail->data);
    }

    public function testUpdateUnsafeAttributes(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(2),
        ]);

        Yii::$app->getI18n()->setLanguages(['de', 'en-US']);

        $form->load([
            $form->formName() => [
                'status' => User::STATUS_ENABLED,
                'country' => 'DE',
                'language' => 'de',
                'is_owner' => true,
            ],
        ]);

        self::assertFalse($form->user->isOwner());
        self::assertFalse($form->user->isEnabled());
        self::assertTrue($form->update());

        $trail = $this->getLastTrailRecord();

        self::assertArrayNotHasKey('status', $trail->data);
        self::assertEquals([], array_diff(array_keys($trail->data), $form->user->getTrailAttributes()));
    }

    protected function getLastTrailRecord(): Trail
    {
        return Trail::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
