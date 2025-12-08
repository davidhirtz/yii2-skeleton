<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\modules\admin\models\forms;

use Codeception\Test\Unit;
use Hirtz\Skeleton\codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\models\Trail;
use Hirtz\Skeleton\modules\admin\models\forms\UserForm;
use Hirtz\Skeleton\tests\support\UnitTester;
use Yii;
use yii\symfonymailer\Message;

class UserFormTest extends Unit
{
    use UserFixtureTrait;

    public UnitTester $tester;

    public function testCreateUser(): void
    {
        $form = UserForm::create();
        self::assertFalse($form->save());

        $form->user->name = 'test-user';
        $form->newPassword = 'password';
        $form->repeatPassword = 'password';
        $form->user->email = 'invalid_email';

        self::assertFalse($form->save());

        $expected = Yii::t('yii', '{attribute} is not a valid email address.', [
            'attribute' => $form->getAttributeLabel('email'),
        ]);

        self::assertEquals($expected, $form->getFirstError('email'));

        $admin = $this->tester->grabUserFixture('admin');
        $form->user->email = $admin->email;

        self::assertFalse($form->save());

        $expected = 'This email address is already used by another user.';
        self::assertEquals($expected, $form->getFirstError('email'));

        $form->user->email = 'test-user@test.com';
        self::assertTrue($form->save());

        $this->tester->dontSeeEmailIsSent();
    }

    public function testCreateUserWithEmailConfirmation(): void
    {
        $form = UserForm::create();
        self::assertFalse($form->save());

        $form->user->name = 'test-user';
        $form->newPassword = 'password';
        $form->repeatPassword = 'password';
        $form->user->email = 'test-user@test.com';
        $form->sendEmail = true;

        self::assertTrue($form->save());


        $subject = Yii::t('skeleton', 'Your {name} Account', ['name' => Yii::$app->name]);

        /** @var Message $message */
        $message = $this->tester->grabLastSentEmail();

        self::assertStringContainsString($subject, $message->getSubject());
        self::assertStringContainsString($form->newPassword, $message->getSymfonyEmail()->getHtmlBody());
    }

    public function testUpdatePassword(): void
    {
        $form = UserForm::create([
            'user' => $this->tester->grabUserFixture('admin'),
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
