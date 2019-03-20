<?php

namespace davidhirtz\yii2\skeleton\composer;

use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
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

    /**
     * @param \yii\base\Application $app
     * @param string $namespace
     */
    public function setMigrationNamespace($app, $namespace)
    {
        if ($app instanceof \davidhirtz\yii2\skeleton\console\Application) {

            $app->on($app::EVENT_BEFORE_ACTION, function (\yii\base\ActionEvent $event) {

                /** @var \davidhirtz\yii2\skeleton\console\controllers\MigrateController $controller */
                $controller = $event->action->controller;

                if ($controller instanceof MigrateController) {
                    $controller->migrationNamespaces[] = $event->data;
                }
            }, $namespace);
        }
    }
}