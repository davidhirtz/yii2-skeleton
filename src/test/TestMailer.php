<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Override;
use yii\mail\MessageInterface;
use yii\symfonymailer\Mailer;

class TestMailer extends Mailer
{
    /**
     * @var MessageInterface[]
     */
    private array $messages = [];

    protected function sendMessage($message): true
    {
        $this->messages[$this->generateMessageFileName()] = $message;
        return true;
    }

    #[Override]
    protected function saveMessage($message): bool
    {
        return $this->sendMessage($message);
    }

    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }

    public function getLastMessage(): ?MessageInterface
    {
        return end($this->messages) ?: null;
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}
