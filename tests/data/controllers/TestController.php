<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\data\controllers;

use Hirtz\Skeleton\web\Controller;
use Hirtz\Skeleton\web\ErrorAction;

class TestController extends Controller
{
    public $layout = '@tests/data/views/layout.php';

    public array $config = [];

    #[\Override]
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
                ...$this->config,
            ],
        ];
    }
}
