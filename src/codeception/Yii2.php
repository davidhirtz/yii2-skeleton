<?php

namespace davidhirtz\yii2\skeleton\codeception;

use Codeception\Lib\ModuleContainer;
use Codeception\TestInterface;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\web\Application;
use Yii;

class Yii2 extends \Codeception\Module\Yii2
{
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $config['applicationClass'] ??= Application::class;

        parent::__construct($moduleContainer, $config);
    }

    public function _before(TestInterface $test): void
    {
        parent::_before($test);
        $this->createAssetDirectory();
    }

    public function _after(TestInterface $test): void
    {
        $this->removeAssetDirectory();
        parent::_after($test);
    }

    protected function createAssetDirectory(): void
    {
        $path = $this->getAssetPath();
        FileHelper::createDirectory($path);
        Yii::$app->getAssetManager()->basePath = $path;
    }

    protected function removeAssetDirectory(): void
    {
        FileHelper::removeDirectory($this->getAssetPath());
    }

    protected function getAssetPath(): string
    {
        return Yii::getAlias('@runtime/assets');
    }
}
