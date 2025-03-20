<?php

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\EmailController;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
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
