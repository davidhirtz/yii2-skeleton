<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Override;
use yii\console\Application;
use yii\console\controllers\HelpController as BaseHelpController;

/**
 * Provides help information about console commands.
 *
 * @template T of Application
 * @extends BaseHelpController<T>
 */
class HelpController extends BaseHelpController
{
    /**
     * Yii reflects the controller classes it finds by scanning the filesystem, but adds every `controllerMap` key
     * unchecked and lets `getCommands()` instantiate it to find out what it is. The admin module maps web
     * controllers, which cannot be constructed while the console application has no `user` component.
     *
     * @return string[]
     */
    #[Override]
    protected function getModuleCommands($module): array
    {
        $prefix = $module instanceof Application ? '' : $module->getUniqueId() . '/';
        $ignored = [];

        foreach ($module->controllerMap as $id => $definition) {
            $class = $this->getControllerClass($definition);

            if ($class !== null && !$this->validateControllerClass($class)) {
                $ignored[] = $prefix . $id;
            }
        }

        return array_values(array_diff(parent::getModuleCommands($module), $ignored));
    }

    /**
     * Returns `null` for a definition whose class cannot be told without building it, which keeps the command.
     */
    protected function getControllerClass(mixed $definition): ?string
    {
        return match (true) {
            is_string($definition) => $definition,
            is_array($definition) => $definition['class'] ?? $definition['__class'] ?? null,
            default => null,
        };
    }
}
