<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Console\Controllers\EmailController;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class EmailControllerTest extends TestCase
{
    public function testActionTest(): void
    {
        $email = 'test@test.com';

        $controller = $this->createEmailController();
        $controller->actionTest($email);

        self::assertStringStartsWith('Testing email functionality ... done', $controller->flushStdOutBuffer());

        $message = $this->mailer->getLastMessage();

        self::assertEquals($email, key($message->getTo()));
        self::assertEquals(Yii::$app->params['email'], key($message->getFrom()));
        self::assertEquals('Test email', $message->getSubject());
    }

    protected function createEmailController(): EmailControllerMock
    {
        return new EmailControllerMock('email', Yii::$app);
    }
}


class EmailControllerMock extends EmailController
{
    use StdOutBufferControllerTrait;
}
