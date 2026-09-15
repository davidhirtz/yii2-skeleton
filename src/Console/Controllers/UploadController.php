<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Upload\Upload;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages uploaded files.
 */
class UploadController extends Controller
{
    use ControllerTrait;

    /**
     * Removes the files of uploads whose record was never saved. The upload action collects them too, with
     * `Upload::$gcProbability`, so this is for an installation that would rather not pay for it in a request.
     */
    public function actionClear(): void
    {
        $count = Upload::getComponent()->collectGarbage();

        $formattedCount = Yii::$app->getFormatter()->asInteger($count);
        $message = $count ? "Deleted $formattedCount abandoned uploads" : 'No abandoned uploads found';

        $this->stdout($message . PHP_EOL, Console::FG_GREEN);
    }
}
