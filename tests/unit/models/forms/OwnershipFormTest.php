<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Yii;

class OwnershipFormTest extends Unit
{
    use UserFixtureTrait;

    public UnitTester $tester;

    public function testWithInvalidName(): void
    {
        $form = OwnershipForm::create();
        $form->name = 'wrong_username';

        $expected = Yii::t('skeleton', 'The user {user} was not found.', [
            'name' => $form->name,
        ]);

        self::assertFalse($form->update());
        self::assertEquals($expected, $form->getFirstError('name'));
    }

    public function testWithDisabledUser(): void
    {
        $form = OwnershipForm::create();
        $form->name = $this->tester->grabUserFixture('disabled')->name;

        $expected = Yii::t('skeleton', 'This user is currently disabled and thus can not be made website owner!');

        self::assertFalse($form->update());
        self::assertEquals($expected, $form->getFirstError('name'));
    }

    public function testWithOwner(): void
    {
        $form = OwnershipForm::create();
        $form->name = $this->tester->grabUserFixture()->name;

        $expected = Yii::t('skeleton', 'This user is already the owner of the website!');

        self::assertFalse($form->update());
        self::assertEquals($expected, $form->getFirstError('name'));
    }

    public function testWithValidUser(): void
    {
        $form = OwnershipForm::create();
        $form->name = $this->tester->grabUserFixture('admin')->name;

        self::assertTrue($form->update());

        $user = User::find()->where(['is_owner' => 1])->one();

        self::assertTrue($user->isOwner());
        self::assertEquals($form->name, $user->name);
    }
}
