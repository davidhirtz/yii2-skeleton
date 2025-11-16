<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\base;

use Override;

class Module extends \yii\base\Module
{
    public const string EVENT_INIT = 'init';

    #[Override]
    public function init(): void
    {
        $this->trigger(self::EVENT_INIT);
        parent::init();
    }

    /**
     * @param class-string[] $config
     * @return array<string, array>
     */
    protected function getFormattedControllerMap(array $config): array
    {
        $controllerMap = [];

        foreach ($config as $id => $class) {
            $controllerMap[$id] = [
                'class' => $class,
                'viewPath' => "@skeleton/modules/admin/views/{$id}",
            ];
        }
        return $controllerMap;
    }
}
