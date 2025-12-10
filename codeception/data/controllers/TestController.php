<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\data\controllers;

use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\ErrorAction;

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
