<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Hirtz\Skeleton\Mail\Mailer;
use Hirtz\Skeleton\Mail\Message;
use Override;

class TestMailer extends Mailer
{
    /**
     * @var Message[]
     */
    private array $messages = [];

    public function init(): void
    {
        $this->useFileTransport = false;
        parent::init();
    }

    /**
     * @param Message $message
     */
    #[Override]
    protected function sendMessage($message): true
    {
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
    }
}
