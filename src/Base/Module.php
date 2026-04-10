<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

use Override;
use ReflectionClass;

class Module extends \yii\base\Module
{
    public const string EVENT_INIT = 'init';

    #[Override]
    public function init(): void
    {
        $this->setViewPath($this->getViewPathFromBasePath());
        $this->trigger(self::EVENT_INIT);

        $this->controllerNamespace ??= (new ReflectionClass(static::class))->getNamespaceName() . '\\Controllers';

        parent::init();
    }

    protected function getViewPathFromBasePath(): string
    {
        $path = $this->getBasePath();
        $parts = explode('/', $path);
        $name = strtolower(array_pop($parts));

        if ($name === 'src') {
            return "$path/../resources/views/";
        }

        while ($parts) {
            $path .= '/..';

            if (array_pop($parts) === 'src') {
                break;
            }
        }

        return "$path/../resources/views/$name/";
    }
}
