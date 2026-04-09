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
        $path = $this->getBasePath();
        $parts = explode('/', $path);
        $name = strtolower(array_pop($parts));

        if ($name === 'src') {
            $this->setViewPath("$path/../resources/views/");
        } else {
            while ($parts && array_pop($parts) !== 'src') {
                $path .= '/..';
            }

            $this->setViewPath("$path/../../resources/views/$name");
        }


        $this->setViewPath($this->getBasePath() . '/../../../resources/views/admin/');
        $this->trigger(self::EVENT_INIT);

        $this->controllerNamespace ??= (new ReflectionClass(static::class))->getNamespaceName() . '\\Controllers';

        parent::init();
    }
}
