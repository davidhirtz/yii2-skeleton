<?php

namespace davidhirtz\yii2\skeleton\composer;

use davidhirtz\yii2\skeleton\console\Application;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
use yii\base\ActionEvent;
use yii\helpers\ArrayHelper;

/**
 * Class BootstrapTrait
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
trait BootstrapTrait
{
    /**
     * Extends given application component.
     *
     * @param \yii\base\Application $app
     * @param string $id
     * @param array $definition
     */
    public function extendComponent($app, $id, $definition)
    {
        $app->set($id, ArrayHelper::merge($app->getComponents()[$id] ?? [], $definition));
    }

    /**
     * Extends multiple application components.
     *
     * @param \yii\base\Application $app
     * @param array $components
     */
    public function extendComponents($app, $components)
    {
        foreach ($components as $id => $definition) {
            $this->extendComponent($app, $id, $definition);
        }
    }

    /**
     * @param \yii\base\Application $app
     * @param string $id
     * @param array $module
     */
    public function extendModule($app, $id, $module)
    {
        if ($module) {
            $app->setModule($id, ArrayHelper::merge($module, $app->getModules()[$id] ?? []));
        }
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
        if ($app instanceof Application) {
            $app->on($app::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
                /** @var MigrateController $controller */
                $controller = $event->action->controller;
                if ($controller instanceof MigrateController) {
                    $controller->migrationNamespaces[] = $event->data;
                }
            }, $namespace);
        }
    }
}