<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Controllers;

use Hirtz\Skeleton\Web\Controller;
use Exception;
use Yii;
use yii\base\Module;

/**
 * @extends Controller<Module>
 */
class HealthController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex(): void
    {
        try {
            Yii::$app->getDb()->open();
        } catch (Exception $exception) {
            Yii::error($exception->getMessage());
            $this->response->setStatusCode(503);
        }
    }
}
