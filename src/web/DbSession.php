<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

class DbSession extends \yii\web\DbSession
{
    use SessionTrait;
}
