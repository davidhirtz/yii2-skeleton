<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mail;

use Hirtz\Skeleton\Mail\Mailer;
use Hirtz\Skeleton\Mail\Message;
use Hirtz\Skeleton\Test\TestCase;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\NullTransport;
use yii\base\InvalidConfigException;
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
}
