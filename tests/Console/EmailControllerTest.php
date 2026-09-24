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

        self::assertEquals($email, $this->mailer->getLastMessageTo());
        self::assertEquals(Yii::$app->params['email'], $this->mailer->getLastMessageFrom());
        self::assertEquals('Test email', $message->getSubject());
    }

    public function testAFailedSendNamesItsReason(): void
    {
        $this->mailer->isFailing = true;

        $controller = $this->createEmailController();
        $controller->actionTest('test@test.com');

        $output = $controller->flushStdOutBuffer();

        self::assertStringStartsWith('Testing email functionality ... failed', $output);
        self::assertStringContainsString('The test transport refuses every message.', $output);
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
