<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Override;
use yii\symfonymailer\Mailer;
use yii\symfonymailer\Message;

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
     * Symfony types the body `string|resource|null` and `MessageInterface::getTo()` an array or a plain string,
     * neither of which a test wants to narrow for itself.
     */
    public function getLastMessageBody(): string
    {
        return (string)$this->getLastMessage()?->getSymfonyEmail()->getHtmlBody();
    }

    public function getLastMessageTo(): string
    {
        return $this->getFirstAddress($this->getLastMessage()?->getTo() ?? []);
    }

    public function getLastMessageFrom(): string
    {
        return $this->getFirstAddress($this->getLastMessage()?->getFrom() ?? []);
    }

    /**
     * @param array<string, string>|string $addresses
     */
    private function getFirstAddress(array|string $addresses): string
    {
        return is_array($addresses) ? (string)key($addresses) : $addresses;
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}
