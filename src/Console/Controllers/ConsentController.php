<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\GarbageCollectionTrait;
use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module;
use Override;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages the cookie consent log garbage collection.
 */
class ConsentController extends Controller
{
    use ControllerTrait;
    use GarbageCollectionTrait;

    #[Override]
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        return $actionID === 'clear' ? [...$options, 'sleep', 'batchSize'] : $options;
    }

    /**
     * Removes consent records older than the configured or given lifetime in seconds.
     */
    public function actionClear(?int $lifetime = null): void
    {
        $lifetime ??= $this->getConsentLifetime();

        if (!$lifetime) {
            throw new InvalidConfigException('Application `consentLifetime` must be set');
        }

        $totalCount = $this->deleteExpiredRecords(Consent::class, $lifetime);

        $formattedCount = Yii::$app->getFormatter()->asInteger($totalCount);
        $message = $totalCount ? "Deleted $formattedCount expired consent records" : 'No expired consent records found';

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);

        if ($totalCount) {
            $this->actionOptimize();
        }
    }

    /**
     * Optimizes the consent table.
     */
    public function actionOptimize(): int
    {
        return $this->optimizeTable(Consent::tableName());
    }

    protected function getConsentLifetime(): int|false
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module->consentLifetime;
    }
}
