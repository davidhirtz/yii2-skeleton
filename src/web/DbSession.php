<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\web;

class DbSession extends \yii\web\DbSession
{
    use SessionTrait;
}
