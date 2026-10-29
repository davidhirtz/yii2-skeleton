<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Models\Traits\StaleSaveTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;
use Yii;

class StaleSaveTraitTest extends TestCase
{
    use UserFixtureTrait;

    public function testASaveOverAnotherOneMadeSinceIsRefused(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        $id = $this->getUserFromFixture('admin')->id;

        $first = StaleSaveUser::findOne($id);
        $second = StaleSaveUser::findOne($id);
        self::assertInstanceOf(StaleSaveUser::class, $first);
        self::assertInstanceOf(StaleSaveUser::class, $second);

        $first->name = 'firsteditor';
        self::assertTrue($first->save(), print_r($first->getErrors(), true));

        $second->name = 'secondeditor';
        $second->loadedAt = time() - 5;

        Yii::$app->language = 'de';

        self::assertFalse($second->save());

        $trail = $second->findUpdateTrailSince();
        self::assertNotNull($trail);

        $formatter = Yii::$app->getFormatter();
        $time = "am {$formatter->asDate($trail->created_at, 'short')} um {$formatter->asTime($trail->created_at, 'short')} geändert";
        self::assertStringContainsString($time, (string)$second->getFirstError('loadedAt'));

        $second->loadedAt = null;

        self::assertTrue($second->save(), print_r($second->getErrors(), true));
    }

    public function testTheWarningFlashIsEncoded(): void
    {
        $user = StaleSaveUser::findOne($this->getUserFromFixture('admin')->id);
        self::assertInstanceOf(StaleSaveUser::class, $user);

        $user->addError('loadedAt', '<b id="x">Å</b>');
        ActiveForm::make()->model($user)->render();

        self::assertSame(['&lt;b id=&quot;x&quot;&gt;Å&lt;/b&gt;'], $this->getWebSession()->getFlash('warning'));
    }
}

class StaleSaveUser extends User implements StaleSaveInterface
{
    use StaleSaveTrait;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getStaleSaveRules(),
        ];
    }
}
