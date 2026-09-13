<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Models\Forms\OwnershipForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class OwnershipFormTest extends TestCase
{
    use UserFixtureTrait;


    public function testWithDisabledUser(): void
    {
        $form = OwnershipForm::create([
            'user' => $this->getUserFromFixture('disabled')
        ]);

        $expected = Yii::t('skeleton', 'OWNERSHIP_USER_CURRENTLY_DISABLED');

        self::assertFalse($form->update());
        self::assertEquals($expected, $form->getFirstError('name'));
    }

    public function testWithOwner(): void
    {
        $form = OwnershipForm::create([
            'user' => $this->getUserFromFixture('owner')
        ]);

        $expected = Yii::t('skeleton', 'OWNERSHIP_USER_ALREADY_OWNER');

        self::assertFalse($form->update());
        self::assertEquals($expected, $form->getFirstError('name'));
    }

    public function testWithValidUser(): void
    {
        $form = OwnershipForm::create([
            'user' => $this->getUserFromFixture('admin')
        ]);

        self::assertTrue($form->update());

        $user = User::find()->where(['is_owner' => 1])->one();

        self::assertTrue($user->isOwner());
        self::assertEquals($form->user->name, $user->name);
    }
}
