<?php

namespace davidhirtz\yii2\skeleton\codeception\traits;

use davidhirtz\yii2\skeleton\console\Application;

trait ConsoleApplicationTrait
{
    protected function createConsoleApplicationMock(): void
    {
        $config = require(__DIR__ . '/../../../tests/config/test.php');
        unset($config['components']['request']['hostInfo']);

        (new Application($config));
    }
}
