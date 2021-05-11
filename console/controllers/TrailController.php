<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages trail garbage collection
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class TrailController extends Controller
{
    /**
     * Removes trail records older than the application `trailLifetime`.
     */
    public function actionClear()
    {
        if ($this->getTrailLifeTime() < 1) {
            throw new InvalidConfigException("Application `trailLifetime` must be set");
        }

        $threshold = (string)(new DateTime())->setTimestamp(time() - $this->getTrailLifeTime());
        $totalCount = 0;

        $query = Trail::find()
            ->select(['id', 'created_at'])
            ->orderBy(['id' => SORT_ASC])
            ->limit(100)
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

                $this->stdout(("Deleting records ... (" . Yii::$app->getFormatter()->asInteger($totalCount) . ")\r"));

                if ($deletedCount == count($rows)) {
                    sleep(1);
                    continue;
                }
            }

            break;
        }

        $this->stdout(($totalCount ? "Deleted {$totalCount} expired trail records" : 'No expired trail records found') . PHP_EOL, Console::FG_GREEN);

        if ($totalCount) {
            $this->actionOptimize();
        }
    }

    /**
     * Optimizes the trail table.
     */
    public function actionOptimize()
    {
        $this->stdout("Optimizing trail table ... ");
        $start = microtime(true);

        try {
            Yii::$app->getDb()->createCommand('OPTIMIZE TABLE ' . Trail::tableName());
            $this->stdout('done (time: ' . sprintf('%.2f', microtime(true) - $start) . 's)' . PHP_EOL);
        } catch (Exception $exception) {
            Yii::error($exception);
            $this->stdout('failed' . PHP_EOL, Console::FG_RED);
        }
    }

    /**
     * @return int|null
     */
    protected function getTrailLifeTime()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module->trailLifetime;
    }
}