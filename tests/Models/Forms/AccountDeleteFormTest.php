<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Models\Forms\AccountDeleteForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountDeleteFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testDeleteWithoutPassword(): void
    {
        $form = $this->createForm();

        self::assertFalse($form->delete());

        $expected = Yii::t('yii', '{attribute} cannot be blank.', [
            'attribute' => $form->getAttributeLabel('value'),
        ]);

        self::assertEquals($expected, $form->getFirstError('value'));
        self::assertNotNull(User::findOne($form->user->id));
    }

    public function testDeleteWithWrongPassword(): void
    {
        $form = $this->createForm();
        $form->value = 'wrong';

        self::assertFalse($form->delete());

        $expected = Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $form->getAttributeLabel('value'),
        ]);

        self::assertEquals($expected, $form->getFirstError('value'));
        self::assertNotNull(User::findOne($form->user->id));
    }

    public function testDeleteWithCorrectPassword(): void
    {
        $form = $this->createForm();
        $form->value = 'password';

        self::assertTrue($form->delete());
        self::assertNull(User::findOne($form->user->id));
    }

    public function testPasswordIsNotExposedAsExpectedValue(): void
    {
        self::assertNull($this->createForm()->getExpectedValue());
    }

    protected function createForm(): AccountDeleteForm
    {
        return AccountDeleteForm::create([
            'user' => $this->getUserFromFixture('admin'),
        ]);
    }
}
