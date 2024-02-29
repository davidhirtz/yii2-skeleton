<?php

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\models\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Symfony\Component\Mime\Email;
use Yii;

class UserFormTest extends Unit
{
    use UserFixtureTrait;

    public UnitTester $tester;

    public function testCreateUser(): void
    {
        $form = UserForm::create();
        self::assertFalse($form->save());

        $form->name = 'test-user';
        $form->newPassword = 'password';
        $form->repeatPassword = 'password';
        $form->email = 'invalid_email';

        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));

        /** @var User $admin */
        $admin = $this->tester->grabFixture('user', 'admin');
        $form->email = $admin->email;

        self::assertFalse($form->save());

        $expected = 'This email address is already used by another user.';
        self::assertEquals($expected, $form->getFirstError('email'));

        $form->email = 'test-user@test.com';
        self::assertTrue($form->save());

        $this->tester->dontSeeEmailIsSent();
    }
    public function testCreateUserWithEmailConfirmation(): void
    {
        $form = UserForm::create();
        self::assertFalse($form->save());

        $form->name = 'test-user';
        $form->newPassword = 'password';
        $form->repeatPassword = 'password';
        $form->email = 'test-user@test.com';
        $form->sendEmail = true;

        self::assertTrue($form->save());

        /** @var Email $email */
        $email = $this->tester->grabLastSentEmail();

        $subject = Yii::t('skeleton', 'Your {name} Account', ['name' => Yii::$app->name]);

        self::assertStringContainsString($subject, $email->getSubject());
        self::assertStringContainsString($form->newPassword, $email->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = UserForm::create([
            'user' => $this->tester->grabFixture('user', 'admin'),
        ]);

        $form->newPassword = 'new_password';
        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('repeatPassword'),
        ]);

        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'wrong_password';
        self::assertFalse($form->save());

        $expected = Yii::t('skeleton', 'The password must match the new password.');
        self::assertEquals($expected, $form->getFirstError('repeatPassword'));

        $form->repeatPassword = 'new_password';
        self::assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        self::assertNull($trail->data);
        self::assertEquals($trail::TYPE_PASSWORD, $trail->type);
    }

    protected function getLastTrailRecord(): Trail
    {
        return Trail::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
