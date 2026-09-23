<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\GarbageCollectionTrait;
use Hirtz\Skeleton\Models\UserToken;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages user token garbage collection.
 */
class UserTokenController extends Controller
{
    use ControllerTrait;
    use GarbageCollectionTrait;

    /**
     * Removes tokens that have expired.
     *
     * A token without an expiry — a two-factor recovery code — is spent rather than aged out and is never collected
     * here.
     */
    public function actionClear(): void
    {
        $totalCount = UserToken::deleteAll(['<', 'expires_at', gmdate('Y-m-d H:i:s')]);

        $formattedCount = Yii::$app->getFormatter()->asInteger($totalCount);
        $message = $totalCount ? "Deleted $formattedCount expired tokens" : 'No expired tokens found';

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);

        if ($totalCount) {
            $this->actionOptimize();
        }
    }

    /**
     * Optimizes the user token table.
     */
    public function actionOptimize(): void
    {
        $this->optimizeTable(UserToken::tableName());
    }
}
