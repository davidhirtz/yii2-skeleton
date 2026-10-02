<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ConsoleEmailTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testTheSenderIsDerivedFromThePinnedHost(): void
    {
        // Added to `$config` rather than declared: a declared one replaces `config/test.php`, the database included
        $this->config['params']['email'] = null;
        $this->config['params']['hostInfo'] = 'https://www.example.com';
        $this->reloadApplication();

        self::assertSame('hostmaster@www.example.com', Yii::$app->params['email']);
    }
}
