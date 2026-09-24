<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Hirtz\Skeleton\Mail\Mailer;
use Hirtz\Skeleton\Mail\Message;
use Override;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class TestMailer extends Mailer
{
    /**
     * @var Message[]
     */
    private array $messages = [];

    /**
     * @var bool whether the transport refuses every message, which `Mail\Mailer` logs and answers with `false`
     */
    public bool $isFailing = false;

    public function init(): void
    {
        $this->useFileTransport = false;
        parent::init();
    }

    /**
     * @param Message $message
     */
    #[Override]
    protected function sendMessage($message): bool
    {
        if ($this->isFailing) {
            $this->setTransport(new class () implements TransportInterface {
                public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                {
                    throw new TransportException('The test transport refuses every message.');
                }

                public function __toString(): string
                {
                    return 'failing://';
                }
            });

            return parent::sendMessage($message);
        }

        $this->messages[$this->generateMessageFileName()] = $message;
        return true;
    }

    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }

    public function getLastMessage(): ?Message
    {
        return end($this->messages) ?: null;
    }

    /**
     * Symfony types the body `string|resource|null`, which no test wants to narrow for itself.
     */
    public function getLastMessageBody(): string
    {
        return (string)$this->getLastMessage()?->email->getHtmlBody();
    }

    public function getLastMessageTo(): string
    {
        return (string)array_key_first($this->getLastMessage()?->getTo() ?? []);
    }

    public function getLastMessageFrom(): string
    {
        return (string)array_key_first($this->getLastMessage()?->getFrom() ?? []);
    }

    public function reset(): void
    {
        $this->messages = [];
        $this->isFailing = false;
    }
}
