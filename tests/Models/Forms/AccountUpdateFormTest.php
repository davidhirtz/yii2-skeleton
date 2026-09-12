<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use DateTime;
use DateTimeZone;
use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountUpdateFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testUpdateNameAttributes(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->user->name = '';
        self::assertFalse($form->save());

        $form->user->name = '\\!//';
        self::assertFalse($form->save());
        $expects = Yii::t('skeleton', 'Username must only contain alphanumeric characters.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->user->name = 'disabled';
        self::assertFalse($form->save());

        $expects = Yii::t('skeleton', 'This username is already used by another user.');
        self::assertEquals($expects, $form->getFirstError('name'));

        $form->user->name = ' administrator ';

        self::assertTrue($form->save());
        self::assertEquals('administrator', $form->user->name);
        self::assertEquals('ad', $form->user->getInitials());

        $trail = $this->getLastTrailRecord();

        self::assertEquals(['name' => ['admin', 'administrator']], $trail->data);

        /** @var User $user */
        $user = $trail->getModelRecord();
        self::assertEquals($user->getTrailModelName(), $form->user->getTrailModelName());
    }

    public function testUpdateTimezoneAttribute(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $form->user->timezone = 'invalid_timezone';
        self::assertFalse($form->save());

        $form->user->timezone = 'America/New_York';
        self::assertTrue($form->save());

        $dateTime = new DateTime('now', new DateTimeZone($form->user->timezone));
        self::assertEquals('GMT ' . $dateTime->format('P'), $form->user->getTimezoneOffset());

        $trail = $this->getLastTrailRecord();

        self::assertEquals(['timezone' => ['Europe/Berlin', $form->user->timezone]], $trail->data);
    }

    public function testUpdateUnsafeAttributes(): void
    {
        $form = AccountUpdateForm::create([
            'user' => $this->getUserFromFixture('disabled'),
        ]);

        Yii::$app->getI18n()->setLanguages(['de', 'en-US']);

        $form->load([
            $form->user->formName() => [
                'status' => User::STATUS_ENABLED,
                'language' => 'de',
                'is_owner' => true,
            ],
        ]);

        self::assertFalse($form->user->isOwner());
        self::assertFalse($form->user->isEnabled());
        self::assertTrue($form->save());

        $trail = $this->getLastTrailRecord();

        self::assertArrayNotHasKey('status', $trail->data);
        self::assertEquals([], array_diff(array_keys($trail->data), $form->user->getTrailAttributes()));
    }

    public function testLoadIgnoresAttributesOfOtherForms(): void
    {
        $form = AccountUpdateForm::create([
            'user' => User::findOne(3),
        ]);

        $email = $form->user->email;

        $form->load([
            $form->user->formName() => [
                'name' => 'renamed',
                'email' => 'hijacked@domain.com',
            ],
        ]);

        self::assertEquals('renamed', $form->user->name);
        self::assertEquals($email, $form->user->email);
    }

    protected function getLastTrailRecord(): Trail
    {
        return Trail::find()->orderBy(['id' => SORT_DESC])->one();
    }
}
