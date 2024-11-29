<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\data\controllers;

use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\web\ErrorAction;

class TestController extends Controller
{
    public $layout = '@tests/data/views/layout.php';

    public array $config = [];

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
