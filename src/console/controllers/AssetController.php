<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use Yii;
use yii\console\Exception;
use yii\helpers\Console;
use yii\helpers\FileHelper;

class AssetController extends \yii\console\controllers\AssetController
{
    /**
     * Removes all published assets.
     */
    public function actionClear()
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $assets = FileHelper::findDirectories($basePath, ['recursive' => false]);

        $total = count($assets);
        $errors = 0;
        $done = 0;

        if (!$total) {
            $this->stdout("All assets are already cleared\n", Console::FG_GREEN);
        } else {
            $prefix = 'Published assets deleted: ';

            Console::startProgress($done, $total, $prefix);

            foreach ($assets as $asset) {
                FileHelper::removeDirectory($asset);
                Console::updateProgress(++$done, $total, $prefix);
            }

            Console::endProgress();

            if ($errors) {
                $this->stdout("{$errors} assets could not be cleared.\n", Console::FG_RED);
            }
        }
    }
}
