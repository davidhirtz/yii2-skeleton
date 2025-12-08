<?php

namespace davidhirtz\yii2\skeleton\test;

use yii\mail\BaseMailer;
use yii\mail\MessageInterface;

class TestMailer extends BaseMailer
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

    protected function saveMessage($message): bool
    {
        return $this->sendMessage($message);
    }

    public function getMessages(): array
    {
        return $this->messages;
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
