<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mail;

use Override;
use Yii;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;

/**
 * Sends through a Symfony transport built from a DSN against Symfony's own transport list, so every installed
 * bridge resolves (`resend+api://` with `symfony/resend-mailer`). The transport is built on the first send. A
 * transport failure is logged and answered `false`, as `MailerInterface::send()` promises; a DSN that does not
 * resolve still throws.
 */
class Mailer extends BaseMailer
{
    public $messageClass = Message::class;

    private TransportInterface|string|null $transport = null;
    private ?TransportExceptionInterface $lastTransportException = null;

    /**
     * @param TransportInterface|array{dsn: string}|string $transport a transport, or its DSN
     */
    public function setTransport(TransportInterface|array|string $transport): void
    {
        $this->transport = is_array($transport) ? $transport['dsn'] : $transport;
    }

    public function getTransport(): TransportInterface
    {
        if ($this->transport === null) {
            throw new InvalidConfigException('No transport was configured.');
        }

        if (is_string($this->transport)) {
            $this->transport = Transport::fromDsn($this->transport);
        }

        return $this->transport;
    }

    #[Override]
    protected function sendMessage($message): bool
    {
        if (!$message instanceof Message) {
            throw new InvalidArgumentException('The message must be an instance of ' . Message::class . '.');
        }

        $transport = $this->getTransport();
        $this->lastTransportException = null;

        try {
            $transport->send($message->email);
        } catch (TransportExceptionInterface $exception) {
            Yii::error($exception, __METHOD__);
            $this->lastTransportException = $exception;

            return false;
        }

        return true;
    }

    /**
     * Why the last send failed, for a caller that shows the reason rather than only `false`.
     */
    public function getLastTransportException(): ?TransportExceptionInterface
    {
        return $this->lastTransportException;
    }
}
