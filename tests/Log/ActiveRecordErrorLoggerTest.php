<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Log;

use Hirtz\Skeleton\Log\ActiveRecordErrorLogger;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use yii\log\Logger;

class ActiveRecordErrorLoggerTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // `TestLogger` only keeps what it is told to keep
        $this->logger->isRecording = true;
    }

    public function testTheDefaultMessageNamesTheModelAndWhatFailed(): void
    {
        $redirect = Redirect::create();
        $redirect->validate();

        ActiveRecordErrorLogger::log($redirect);

        $message = $this->getLastMessage();

        self::assertStringContainsString('Redirect record with ID  could not be inserted', $message[0]);
        self::assertStringContainsString('request_uri', $message[0]);
        self::assertSame(Logger::LEVEL_WARNING, $message[1]);
        self::assertSame('application', $message[2]);
    }

    public function testAnExistingRecordIsReportedAsAnUpdate(): void
    {
        $redirect = $this->createRedirect();
        $redirect->request_uri = '';
        $redirect->validate();

        ActiveRecordErrorLogger::log($redirect);

        self::assertStringContainsString(
            "Redirect record with ID $redirect->id could not be updated",
            $this->getLastMessage()[0]
        );
    }

    public function testAGivenMessageIsUsedAsItStands(): void
    {
        $redirect = $this->createRedirect();

        ActiveRecordErrorLogger::log($redirect, 'Something specific went wrong');

        $message = $this->getLastMessage();

        // a record without errors contributes nothing beyond the message
        self::assertSame('Something specific went wrong', $message[0]);
    }

    private function createRedirect(): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'old-page';
        $redirect->url = 'new-page';

        self::assertTrue($redirect->insert());

        return $redirect;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getLastMessage(): array
    {
        $messages = array_values(array_filter(
            $this->logger->messages,
            fn (array $message): bool => is_string($message[0]) && $message[2] === 'application'
        ));

        self::assertNotEmpty($messages);

        return end($messages);
    }
}
