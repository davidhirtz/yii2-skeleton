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
}
