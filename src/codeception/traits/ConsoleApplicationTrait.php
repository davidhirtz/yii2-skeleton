<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\codeception\traits;

use Hirtz\Skeleton\console\Application;

trait ConsoleApplicationTrait
{
    protected function createConsoleApplicationMock(): void
    {
        $config = require(__DIR__ . '/../../../tests/config/test.php');
        (new Application($config));
    }
}
