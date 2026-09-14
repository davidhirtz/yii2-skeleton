<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\GarbageCollectionTrait;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Modules\Admin\Module;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages login history garbage collection.
 */
class UserLoginController extends Controller
{
    use ControllerTrait;
    use GarbageCollectionTrait;

    /**
     * Removes login records older than the threshold defined in the module configuration. Alternatively, the
     * lifetime in seconds can be passed as an argument.
     */
    public function actionClear(?int $lifetime = null): void
    {
        $lifetime ??= $this->getUserLoginLifetime();

        if (!$lifetime) {
            throw new InvalidConfigException('Application `userLoginLifetime` must be set');
        }

        $totalCount = $this->deleteExpiredRecords(UserLogin::class, $lifetime);

        $formattedCount = Yii::$app->getFormatter()->asInteger($totalCount);
        $message = $totalCount ? "Deleted $formattedCount expired login records" : 'No expired login records found';

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);

        if ($totalCount) {
            $this->actionOptimize();
        }
    }

    /**
     * Optimizes the login table.
     */
    public function actionOptimize(): void
    {
        $this->optimizeTable(UserLogin::tableName());
    }

    protected function getUserLoginLifetime(): int|false
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module->userLoginLifetime;
    }
}
