<?php

namespace davidhirtz\yii2\skeleton\composer;

use yii\helpers\ArrayHelper;

/**
 * Class BootstrapTrait
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
trait BootstrapTrait
{
    /**
     * @param \yii\base\Application $app
     * @param string $id
     * @param array $config
     */
    public function extendComponent($app, $id, $config)
    {
        $components = isset($app->getComponents()[$id]) ? $app->getComponents()[$id] : [];
        $app->setComponents([$id => ArrayHelper::merge($config, $components)]);
    }

    /**
     * @param \yii\base\Application $app
     * @param array $components
     */
    public function extendComponents($app, $components)
    {
        foreach ($components as $id => $config) {
            $this->extendComponent($app, $id, $config);
        }
    }

    /**
     * @param \yii\base\Application $app
     * @param string $id
     * @param array $config
     */
    public function extendModule($app, $id, $config)
    {
        $module = isset($app->getModules()[$id]) ? $app->getModules()[$id] : [];
        $app->setModule($id, ArrayHelper::merge($config, $module));
    }

    /**
     * @param \yii\base\Application $app
     * @param array $modules
     */
    public function extendModules($app, $modules)
    {
        foreach ($modules as $id => $config) {
            $this->extendModule($app, $id, $config);
        }
    }
}