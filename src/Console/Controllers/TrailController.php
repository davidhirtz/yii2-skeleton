<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\GarbageCollectionTrait;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Module;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages trail garbage collection.
 */
class TrailController extends Controller
{
    use ControllerTrait;
    use GarbageCollectionTrait;

    /**
     * Updates the model classes in the trail table to the current class names based on the container definitions.
     */
    public function actionUpdateModels(?string $filter = '\\Models\\'): void
    {
        $classNames = [];

        foreach (Yii::$container->getDefinitions() as $definition => $options) {
            if (!$filter || str_contains((string)$definition, $filter)) {
                $classNames[$definition] = $options['class'];
            }
        }

        foreach ($classNames as $oldName => $newName) {
            $updated = Trail::updateAll(['model_class' => $newName], ['model_class' => $oldName]);

            if ($updated) {
                $updated = Yii::$app->getFormatter()->asInteger($updated);
                $this->stdout("Updated $updated $newName trail records" . PHP_EOL, Console::FG_GREEN);
            }
        }
    }

    /**
     * Removes trail records older than the threshold defined in the module configuration. Alternatively, the lifetime
     * in seconds can be passed as an argument.
     */
    public function actionClear(?int $lifetime = null): void
    {
        $lifetime ??= $this->getTrailLifeTime();

        if (!$lifetime) {
            throw new InvalidConfigException('Application `trailLifetime` must be set');
        }

        $totalCount = $this->deleteExpiredRecords(Trail::class, $lifetime);

        $formattedCount = Yii::$app->getFormatter()->asInteger($totalCount);
        $message = $totalCount ? "Deleted $formattedCount expired trail records" : 'No expired trail records found';

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);

        if ($totalCount) {
            $this->actionOptimize();
        }
    }

    /**
     * Optimizes the trail table.
     */
    public function actionOptimize(): void
    {
        $this->optimizeTable(Trail::tableName());
    }

    protected function getTrailLifeTime(): ?int
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module->trailLifetime;
    }
}
