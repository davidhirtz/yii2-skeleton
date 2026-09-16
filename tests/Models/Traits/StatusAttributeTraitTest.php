<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Traits\DraftStatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Model;

class StatusAttributeTraitTest extends TestCase
{
    public function testDefinitionsAreIndexedByValue(): void
    {
        $model = $this->createModel();

        self::assertSame(
            [$model::STATUS_ENABLED, $model::STATUS_DISABLED],
            array_keys($model::getStatusDefinitions()),
        );
    }

    public function testTheDelegatesReadTheDefinition(): void
    {
        $model = $this->createModel();
        $model->status = $model::STATUS_ENABLED;

        self::assertInstanceOf(Status::class, $model->getStatus());
        self::assertSame(Yii::t('skeleton', 'COMMON_ENABLED'), $model->getStatusName());
        self::assertSame('globe', $model->getStatusIcon());
    }

    public function testAnUndeclaredValueHasNoStatus(): void
    {
        $model = $this->createModel();
        $model->status = 99;

        self::assertNull($model->getStatus());
        self::assertSame('', $model->getStatusName());
        self::assertSame('', $model->getStatusIcon());
    }

    public function testTheUserStatusesKeepTheOwnerSpecialCase(): void
    {
        $user = User::create();
        $user->status = User::STATUS_ENABLED;

        self::assertSame(Yii::t('skeleton', 'COMMON_ENABLED'), $user->getStatusName());
        self::assertSame('user', $user->getStatusIcon());
    }

    public function testTheNextStatusWrapsAtTheEndOfTheList(): void
    {
        $model = $this->createModel();

        $model->status = $model::STATUS_ENABLED;
        self::assertSame($model::STATUS_DISABLED, $model->getNextStatus()?->value);

        $model->status = $model::STATUS_DISABLED;
        self::assertSame($model::STATUS_ENABLED, $model->getNextStatus()?->value);
    }

    /**
     * A record stored with a status the configuration no longer declares is left alone rather than moved to the
     * first one, which would silently retype it.
     */
    public function testAnUndeclaredValueHasNoNextStatus(): void
    {
        $model = $this->createModel();
        $model->status = 99;

        self::assertNull($model->getNextStatus());
    }

    public function testASingleStatusHasNoNextStatus(): void
    {
        $model = new SingleStatusModel();
        $model->status = $model::STATUS_ENABLED;

        self::assertNull($model->getNextStatus());
    }

    public function testTheDraftStatusIsPartOfTheCycle(): void
    {
        $model = new DraftStatusModel();

        $model->status = $model::STATUS_ENABLED;
        self::assertSame($model::STATUS_DRAFT, $model->getNextStatus()?->value);

        $model->status = $model::STATUS_DRAFT;
        self::assertSame($model::STATUS_DISABLED, $model->getNextStatus()?->value);

        $model->status = $model::STATUS_DISABLED;
        self::assertSame($model::STATUS_ENABLED, $model->getNextStatus()?->value);
    }

    /**
     * The owner's star is not a status, so there is nothing to cycle through.
     */
    public function testTheSiteOwnerIsNotStatusUpdatable(): void
    {
        $user = User::create();
        $user->status = User::STATUS_ENABLED;

        self::assertTrue($user->isStatusUpdatable());

        $user->is_owner = true;

        self::assertFalse($user->isStatusUpdatable());
        self::assertSame('star', $user->getStatusIcon());
    }

    public function testTheCacheIsResetWithTheApplication(): void
    {
        $definitions = User::getStatusDefinitions();
        self::assertSame($definitions, User::getStatusDefinitions());

        $this->reloadApplication();

        self::assertNotSame($definitions, User::getStatusDefinitions());
    }

    private function createModel(): StatusModel
    {
        return new StatusModel();
    }
}

class StatusModel extends Model implements StatusAttributeInterface
{
    use StatusAttributeTrait;

    public ?int $status = self::STATUS_DEFAULT;
}

class SingleStatusModel extends StatusModel
{
    #[Override]
    public function getStatuses(): array
    {
        return [Status::make(self::STATUS_ENABLED)->name('Enabled')->icon('globe')];
    }
}

class DraftStatusModel extends Model implements DraftStatusAttributeInterface
{
    use DraftStatusAttributeTrait;

    public ?int $status = self::STATUS_DEFAULT;
}
