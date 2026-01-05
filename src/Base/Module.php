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
        $this->setViewPath($this->getBasePath() . '/../resources/views');
        $this->trigger(self::EVENT_INIT);

        $this->controllerNamespace ??= (new ReflectionClass(static::class))->getNamespaceName() . '\\Controllers';

        parent::init();
    }
}
