<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules;

use Hirtz\Skeleton\Modules\Admin\Module;
use Yii;

trait ModuleTrait
{
    public static function getModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}
