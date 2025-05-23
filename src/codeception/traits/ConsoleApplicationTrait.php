<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\codeception\traits;

use davidhirtz\yii2\skeleton\console\Application;

trait ConsoleApplicationTrait
{
    protected function createConsoleApplicationMock(): void
    {
        $config = require(__DIR__ . '/../../../tests/config/test.php');
        (new Application($config));
    }
}
