<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\web;

class CacheSession extends \yii\web\CacheSession
{
    use SessionTrait;
}
