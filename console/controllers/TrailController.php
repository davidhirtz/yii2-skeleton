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
     * Removes trail records older than the application `trailLifetime`.
     */
    public function actionClear()
    {
        if ($this->getTrailLifeTime() < 1) {
            throw new InvalidConfigException("Application `trailLifetime` must be set");
        }

        $lastId = Trail::find()
            ->select('id')
            ->where(['<', 'created_at', (string)(new DateTime())->setTimestamp(time() - $this->getTrailLifeTime())])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->scalar();

        $deleteCount = Trail::deleteAll(['<=', 'id', $lastId]);
        $this->stdout(($deleteCount ? "Deleted {$deleteCount} expired trail records" : "No expired trail records found") . PHP_EOL, Console::FG_GREEN);
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