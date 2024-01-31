<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use DateTime;
use DateTimeZone;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixture;
use davidhirtz\yii2\skeleton\models\forms\UserForm;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Symfony\Component\Mime\Email;
use Yii;

/**
 * @property UnitTester $tester
 */
class UserFormTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ];
    }

    public function testUpdateEmailAddress(): void
    {
        $form = UserForm::findOne(3);

        $form->email = 'invalid_email';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'owner@domain.com';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'This email address is already used by another user.');
        self::assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'valid@domain.com';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        self::assertTrue($form->save());
        self::assertNotEmpty($form->verification_token);

        /** @var Email $email */
        $email = $this->tester->grabLastSentEmail();
        self::assertStringContainsString($form->getEmailConfirmationUrl(), $email->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = UserForm::findOne(3);
        $form->passwordMinLength = strlen('new_password');

        $form->newPassword = 'short';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.', [
            'attribute' => $form->getAttributeLabel('newPassword'),
            'min' => $form->passwordMinLength,
        ]);

        self::assertEquals($expected, $form->getFirstError('newPassword'));

        $form->newPassword = 'new_password';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('repeatPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'wrong_new_password';
        $form->oldPassword = 'password';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'The password must match the new password.');
        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'new_password';
        self::assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        self::assertNull($trail->data);
        self::assertEquals($trail::TYPE_PASSWORD, $trail->type);
    }

    public function testUpdateNameAttributes(): void
    {
        $form = UserForm::findOne(3);

        $form->name = '';
        self::assertFalse($form->save());

        $form->name = '\\!//';
        self::assertFalse($form->save());
        $expects = Yii::t('skeleton', 'Username must only contain alphanumeric characters.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'disabled';
        self::assertFalse($form->save());

        $expects = Yii::t('skeleton', 'This username is already used by another user.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'admin';
        $form->first_name = ' Test ';
        $form->last_name = 'User';

        self::assertTrue($form->save());
        self::assertEquals('Test', $form->first_name);
        self::assertEquals('Test User', $form->getFullName());
        self::assertEquals('TU', $form->getInitials());

        $trail = $this->getLastTrailRecord();

        $expects = [
            'first_name' => [null, 'Test'],
            'last_name' => [null, 'User'],
        ];

        self::assertEquals($expects, $trail->data);

        /** @var User $user */
        $user = $trail->getModelClass();
        self::assertEquals($user->getTrailModelName(), $form->getTrailModelName());
    }

    public function testUpdateTimezoneAttribute(): void
    {
        $form = UserForm::findOne(3);

        $form->timezone = 'invalid_timezone';
        self::assertFalse($form->save());

        $form->timezone = 'America/New_York';
        self::assertTrue($form->save());

        $dateTime = new DateTime('now', new DateTimeZone($form->timezone));
        self::assertEquals('GMT ' . $dateTime->format('P'), $form->getTimezoneOffset());

        $trail = $this->getLastTrailRecord();

        self::assertEquals(['timezone' => ['Europe/Berlin', $form->timezone]], $trail->data);
    }

    public function testUpdateUnsafeAttributes(): void
    {
        $form = UserForm::findOne(2);
        Yii::$app->getI18n()->setLanguages(['de', 'en-US']);

        $form->load([
            $form->formName() => [
                'status' => User::STATUS_ENABLED,
                'country' => 'DE',
                'language' => 'de',
                'is_owner' => true,
            ],
        ]);

        self::assertFalse($form->isOwner());
        self::assertFalse($form->isEnabled());
        self::assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        self::assertArrayNotHasKey('status', $trail->data);
        self::assertEquals([], array_diff(array_keys($trail->data), $form->getTrailAttributes()));
    }

    protected function getLastTrailRecord(): Trail
    {
        return Trail::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
