<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\modules\admin\Module;
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
     * Removes trail records older than the application `trailLifetime`. As there is currently no index on created at
     * we need to delete the records in batch.
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
                    continue;
                }
            }

            break;
        }

        $this->stdout(($totalCount ? "Deleted {$totalCount} expired trail records" : "No expired trail records found") . PHP_EOL, Console::FG_GREEN);
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