<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use yii\console\Exception;
use Yii;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Class AssetController
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class AssetController extends \yii\console\controllers\AssetController
{
    /**
     * @var string
     */
    public $defaultAction = 'auto';

    /**
     * Creates asset bundles based on config.
     */
    public function actionAuto()
    {
        $manager = Yii::$app->getAssetManager();
        $manager->combineOptions['assetManager'] = $manager;

        /** @noinspection PhpParamsInspection */
        $this->actionCompress($manager->combineOptions, Yii::getAlias($manager->combinedBundlesAlias));
    }

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

    /**
     * @param string $configFile
     * @throws Exception
     */
    protected function loadConfiguration($configFile)
    {
        if (is_array($configFile)) {
            foreach ($configFile as $name => $value) {
                if (property_exists($this, $name) || $this->canSetProperty($name)) {
                    $this->$name = $value;
                } else {
                    throw new Exception("Unknown combined configuration option: $name");
                }
            }
        } else {
            parent::loadConfiguration($configFile);
        }
    }
}