<?php

namespace davidhirtz\yii2\skeleton\composer;

use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * Shared application configuration after init.
     * @param Application|\davidhirtz\yii2\skeleton\console\Application $app
     */
    public function bootstrap($app)
    {
    }
}