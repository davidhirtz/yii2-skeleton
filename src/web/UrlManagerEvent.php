<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use yii\base\Event;

class UrlManagerEvent extends Event
{
    public ?string $url = null;
    public ?array $params = null;
    public ?Request $request = null;
}
