<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
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
