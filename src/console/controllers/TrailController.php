<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ControllerTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use Exception;
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

    /**
     * Updates the model classes in the trail table to the current class names based on the container definitions.
     */
    public function actionUpdateModels(?string $filter = '\\models\\'): void
    {
        $classNames = [];

        foreach (Yii::$container->getDefinitions() as $definition => $options) {
            if (!$filter || str_contains((string)$definition, $filter)) {
                $classNames[$definition] = $options['class'];
            }
        }

        foreach ($classNames as $oldName => $newName) {
            $updated = Trail::updateAll(['model' => $newName], ['model' => $oldName]);

            if ($updated) {
                $updated = Yii::$app->getFormatter()->asInteger($updated);
                $this->stdout("Updated $updated $newName trail records" . PHP_EOL, Console::FG_GREEN);
            }
        }
    }

    /**
     * Removes trail records older than the threshold defined in the module configuration. Alternatively, the lifetime
     * in seconds can be passed as an argument.
     *
     * This method orders the records by their primary key and deletes them in batches of 100 records. This is done to
     * prevent locking the table for too long or applying an SQL query with a too large `WHERE` clause.
     */
    public function actionClear(?int $lifetime = null): void
    {
        $lifetime ??= $this->getTrailLifeTime();

        if (!$lifetime) {
            throw new InvalidConfigException('Application `trailLifetime` must be set');
        }

        $threshold = gmdate('Y-m-d H:i:s', time() - $lifetime);
        $totalCount = 0;
        $limit = 100;

        $query = Trail::find()
            ->select(['id', 'created_at'])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->asArray();

        while (true) {
            $rows = $query->all();
            $ids = [];

            foreach ($rows as $row) {
                if ($row['created_at'] < $threshold) {
                    $ids[] = $row['id'];
                }
            }

            if ($ids) {
                $deletedCount = Trail::deleteAll(['id' => $ids]);
                $totalCount += $deletedCount;

                $count = Yii::$app->getFormatter()->asInteger($totalCount);
                $this->stdout("Deleting records ... ($count)\n");

                if ($deletedCount == count($rows)) {
                    // If all records were deleted, we can continue with the next batch, to prevent a database shutdown
                    // for super large tables, we wait a second before continuing.
                    if (count($rows) == $limit) {
                        sleep(1);
                    }

                    continue;
                }
            }

            break;
        }

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
        $this->interactiveStartStdout('Optimizing trail table... ');
        $success = false;

        try {
            Yii::$app->getDb()->createCommand('OPTIMIZE TABLE ' . Trail::tableName());
            $success = true;
        } catch (Exception $exception) {
            Yii::error($exception);
        }

        $this->interactiveDoneStdout($success);
    }

    protected function getTrailLifeTime(): ?int
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module->trailLifetime;
    }
}
