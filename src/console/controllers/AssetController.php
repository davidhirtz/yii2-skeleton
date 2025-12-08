<?php

declare(strict_types=1);

/**
 * @noinspection PhpUnused
 */

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\traits\ControllerTrait;
use Hirtz\Skeleton\Helpers\FileHelper;
use Yii;
use yii\helpers\Console;

class AssetController extends \yii\console\controllers\AssetController
{
    use ControllerTrait;

    /**
     * Removes all published assets.
     */
    public function actionClear(): void
    {
        $assets = FileHelper::findDirectories(Yii::$app->getAssetManager()->basePath, [
            'recursive' => false,
        ]);

        $total = count($assets);

        if (!$total) {
            $this->stdout('All assets are already cleared' . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->interactiveStartStdout('Removing ' . ($total === 1 ? 'one asset bundle' : "$total asset bundles") . ' ... ');

            foreach ($assets as $asset) {
                FileHelper::removeDirectory($asset);
            }

            $this->interactiveDoneStdout();
        }
    }
}
