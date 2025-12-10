<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\console;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Codeception\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Console\Controllers\EmailController;
use Hirtz\Skeleton\Tests\support\UnitTester;
use Yii;
use yii\symfonymailer\Message;

/**
 * @property UnitTester $tester
 */
class EmailControllerTest extends Unit
{
    public function testActionTest(): void
    {
        $email = 'test@test.com';

        $controller = $this->createEmailController();
        $controller->actionTest($email);

        self::assertStringStartsWith('Testing email functionality ... done', $controller->flushStdOutBuffer());

        /** @var Message $message */
        $message = $this->tester->grabLastSentEmail();

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
