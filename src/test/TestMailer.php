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

    public function reset(): void
    {
        $this->messages = [];
    }
}
