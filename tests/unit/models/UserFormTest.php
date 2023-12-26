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
        $this->assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        $this->assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'owner@domain.com';
        $this->assertFalse($form->save());

        $expected = Yii::t('skeleton', 'This email address is already used by another user.');
        $this->assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'valid@domain.com';
        $this->assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        $this->assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        $this->assertTrue($form->save());
        $this->assertNotEmpty($form->verification_token);

        /** @var Email $email */
        $email = $this->tester->grabLastSentEmail();
        $this->assertStringContainsString($form->getEmailConfirmationUrl(), $email->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = UserForm::findOne(3);
        $form->passwordMinLength = strlen('new_password');

        $form->newPassword = 'short';
        $this->assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.', [
            'attribute' => $form->getAttributeLabel('newPassword'),
            'min' => $form->passwordMinLength,
        ]);

        $this->assertEquals($expected, $form->getFirstError('newPassword'));

        $form->newPassword = 'new_password';
        $this->assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('oldPassword'),
        ]);

        $this->assertEquals($expected, $form->getFirstError('oldPassword'));

        $form->oldPassword = 'password';
        $this->assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('repeatPassword'),
        ]);

        $this->assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'wrong_new_password';
        $form->oldPassword = 'password';
        $this->assertFalse($form->save());

        $expected = Yii::t('skeleton', 'The password must match the new password.');
        $this->assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'new_password';
        $this->assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        $this->assertNull($trail->data);
        $this->assertEquals($trail::TYPE_PASSWORD, $trail->type);
    }

    public function testUpdateNameAttributes(): void
    {
        $form = UserForm::findOne(3);

        $form->name = '';
        $this->assertFalse($form->save());

        $form->name = '\\!//';
        $this->assertFalse($form->save());
        $expects = Yii::t('skeleton', 'Username must only contain alphanumeric characters.');
        $this->assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'disabled';
        $this->assertFalse($form->save());

        $expects = Yii::t('skeleton', 'This username is already used by another user.');
        $this->assertEquals($expects, $form->getFirstError('name'));

        $form->name = 'admin';
        $form->first_name = ' Test ';
        $form->last_name = 'User';

        $this->assertTrue($form->save());
        $this->assertEquals('Test', $form->first_name);
        $this->assertEquals('Test User', $form->getFullName());
        $this->assertEquals('TU', $form->getInitials());

        $trail = $this->getLastTrailRecord();

        $expects = [
            'first_name' => [null, 'Test'],
            'last_name' => [null, 'User'],
        ];

        $this->assertEquals($expects, $trail->data);

        /** @var User $user */
        $user = $trail->getModelClass();
        $this->assertEquals($user->getTrailModelName(), $form->getTrailModelName());
    }

    public function testUpdateTimezoneAttribute(): void
    {
        $form = UserForm::findOne(3);

        $form->timezone = 'invalid_timezone';
        $this->assertFalse($form->save());

        $form->timezone = 'America/New_York';
        $this->assertTrue($form->save());

        $dateTime = new DateTime('now', new DateTimeZone($form->timezone));
        $this->assertEquals('GMT ' . $dateTime->format('P'), $form->getTimezoneOffset());

        $trail = $this->getLastTrailRecord();

        $this->assertEquals(['timezone' => ['Europe/Berlin', $form->timezone]], $trail->data);
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

        $this->assertFalse($form->isOwner());
        $this->assertFalse($form->isEnabled());
        $this->assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        $this->assertArrayNotHasKey('status', $trail->data);
        $this->assertEquals([], array_diff(array_keys($trail->data), $form->getTrailAttributes()));
    }

    protected function getLastTrailRecord(): Trail
    {
        return Trail::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
