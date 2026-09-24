<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mail;

use Hirtz\Skeleton\Mail\Mailer;
use Hirtz\Skeleton\Mail\Message;
use Hirtz\Skeleton\Test\TestCase;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use yii\base\InvalidConfigException;
use yii\log\Logger;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

class MailerTest extends TestCase
{
    public function testAnInstalledBridgeResolvesItsDsn(): void
    {
        $mailer = new Mailer(['transport' => ['dsn' => 'resend+api://re_test@default']]);
        self::assertInstanceOf(ResendApiTransport::class, $mailer->getTransport());
    }

    public function testTheTransportIsBuiltOnFirstUse(): void
    {
        $mailer = new Mailer(['transport' => 'unknown://default']);

        $this->expectException(UnsupportedSchemeException::class);
        $mailer->getTransport();
    }

    public function testATransportInstanceIsKept(): void
    {
        $transport = new NullTransport();
        $mailer = new Mailer(['transport' => $transport]);

        self::assertSame($transport, $mailer->getTransport());
    }

    public function testAMailerWithoutTransportCannotSend(): void
    {
        $this->expectException(InvalidConfigException::class);
        (new Mailer())->getTransport();
    }

    public function testAComposedMessageIsSent(): void
    {
        $mailer = new Mailer(['transport' => 'null://null']);
        $sent = null;

        $mailer->on(BaseMailer::EVENT_AFTER_SEND, function (MailEvent $event) use (&$sent): void {
            $sent = $event->isSuccessful ? $event->message : null;
        });

        $message = $mailer->compose()
            ->setFrom('sender@example.com')
            ->setTo('recipient@example.com')
            ->setSubject('Subject')
            ->setTextBody('Body');

        self::assertInstanceOf(Message::class, $message);
        self::assertTrue($message->send());
        self::assertSame($message, $sent);
    }

    public function testATransportFailureIsLoggedAndAnsweredFalse(): void
    {
        $this->logger->isRecording = true;

        $mailer = new Mailer(['transport' => new class () implements TransportInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Refused');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        }]);

        $sent = $mailer->compose()
            ->setFrom('sender@example.com')
            ->setTo('recipient@example.com')
            ->setTextBody('Body')
            ->send();

        self::assertFalse($sent);
        self::assertInstanceOf(TransportException::class, $mailer->getLastTransportException());

        $errors = array_values(array_filter(
            $this->logger->messages,
            static fn (array $message): bool => $message[1] === Logger::LEVEL_ERROR,
        ));

        self::assertCount(1, $errors);
        self::assertInstanceOf(TransportException::class, $errors[0][0]);
        self::assertSame(Mailer::class . '::sendMessage', $errors[0][2]);
    }
}
