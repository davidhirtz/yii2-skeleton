<?php

namespace davidhirtz\yii2\skeleton\codeception;

use Codeception\Lib\ModuleContainer;
use davidhirtz\yii2\skeleton\web\Application;

class Yii2 extends \Codeception\Module\Yii2
{
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $config['applicationClass'] ??= Application::class;

        parent::__construct($moduleContainer, $config);
    }
}
