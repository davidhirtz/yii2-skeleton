<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Actions;

use Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord;
use Hirtz\Skeleton\Models\Events\DuplicateActiveRecordEvent;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class DuplicateActiveRecordTest extends TestCase
{
    public function testAFailedDuplicateRollsBackItsTransaction(): void
    {
        // The test's own transaction would make the action join it instead of opening one
        Yii::$app->getDb()->getTransaction()?->rollBack();

        $user = new User();
        $user->on(DuplicateActiveRecord::EVENT_BEFORE_DUPLICATE, function (DuplicateActiveRecordEvent $event): void {
            $event->isValid = false;
        });

        self::assertFalse((new DuplicateActiveRecord($user))->duplicateActiveRecord());
        self::assertNull(Yii::$app->getDb()->getTransaction());
    }
}
