<?php

namespace davidhirtz\yii2\skeleton\web;

use yii\base\Event;

class UrlManagerEvent extends Event
{
    public ?string $url = null;
    public ?array $params = null;
    public ?Request $request = null;
}
