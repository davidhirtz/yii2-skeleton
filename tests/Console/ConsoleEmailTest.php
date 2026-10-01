<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ConsoleEmailTest extends TestCase
{
    protected string $applicationClass = Application::class;

    protected array $config = [
        'params' => [
            'email' => null,
            'hostInfo' => 'https://www.example.com',
        ],
    ];

    public function testTheSenderIsDerivedFromThePinnedHost(): void
    {
        self::assertSame('hostmaster@www.example.com', Yii::$app->params['email']);
    }
}
