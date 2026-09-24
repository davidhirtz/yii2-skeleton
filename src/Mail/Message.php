<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mail;

use Override;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use yii\mail\BaseMessage;

/**
 * Yii's message interface over a Symfony `Email`; anything beyond it (headers, priority, signing) is done on
 * `$email`. Addresses are `'a@b.c'`, `['a@b.c', …]` or `['a@b.c' => 'Name', …]` and are read back as
 * `['a@b.c' => 'Name']`, the name empty where none was given.
 */
class Message extends BaseMessage
{
    public readonly Email $email;
    private string $charset = 'utf-8';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->email = new Email();
        parent::__construct($config);
    }

    public function __clone()
    {
        $this->email = clone $this->email;
    }

    #[Override]
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Applies to the bodies set after it.
     *
     * @param string $charset
     */
    #[Override]
    public function setCharset($charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getFrom(): array
    {
        return $this->getAddresses($this->email->getFrom());
    }

    /**
     * @param array<int|string, string>|string $from
     */
    #[Override]
    public function setFrom($from): static
    {
        $this->email->from(...$this->createAddresses($from));
        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getTo(): array
    {
        return $this->getAddresses($this->email->getTo());
    }

    /**
     * @param array<int|string, string>|string $to
     */
    #[Override]
    public function setTo($to): static
    {
        $this->email->to(...$this->createAddresses($to));
        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getReplyTo(): array
    {
        return $this->getAddresses($this->email->getReplyTo());
    }

    /**
     * @param array<int|string, string>|string $replyTo
     */
    #[Override]
    public function setReplyTo($replyTo): static
    {
        $this->email->replyTo(...$this->createAddresses($replyTo));
        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getCc(): array
    {
        return $this->getAddresses($this->email->getCc());
    }

    /**
     * @param array<int|string, string>|string $cc
     */
    #[Override]
    public function setCc($cc): static
    {
        $this->email->cc(...$this->createAddresses($cc));
        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getBcc(): array
    {
        return $this->getAddresses($this->email->getBcc());
    }

    /**
     * @param array<int|string, string>|string $bcc
     */
    #[Override]
    public function setBcc($bcc): static
    {
        $this->email->bcc(...$this->createAddresses($bcc));
        return $this;
    }

    #[Override]
    public function getSubject(): string
    {
        return (string)$this->email->getSubject();
    }

    /**
     * @param string $subject
     */
    #[Override]
    public function setSubject($subject): static
    {
        $this->email->subject($subject);
        return $this;
    }

    /**
     * @param string $text
     */
    #[Override]
    public function setTextBody($text): static
    {
        $this->email->text($text, $this->charset);
        return $this;
    }

    /**
     * @param string $html
     */
    #[Override]
    public function setHtmlBody($html): static
    {
        $this->email->html($html, $this->charset);
        return $this;
    }

    /**
     * @param string $fileName
     * @param array{fileName?: string, contentType?: string} $options
     */
    #[Override]
    public function attach($fileName, array $options = []): static
    {
        $this->email->attachFromPath(
            $fileName,
            $options['fileName'] ?? basename($fileName),
            $options['contentType'] ?? FileHelper::getMimeType($fileName),
        );

        return $this;
    }

    /**
     * @param resource|string $content
     * @param array{fileName?: string, contentType?: string} $options
     */
    #[Override]
    public function attachContent($content, array $options = []): static
    {
        $this->email->attach($content, $options['fileName'] ?? null, $options['contentType'] ?? null);
        return $this;
    }

    /**
     * @param string $fileName
     * @param array{fileName?: string, contentType?: string} $options
     */
    #[Override]
    public function embed($fileName, array $options = []): string
    {
        $name = $options['fileName'] ?? basename($fileName);
        $this->email->embedFromPath($fileName, $name, $options['contentType'] ?? FileHelper::getMimeType($fileName));

        return "cid:$name";
    }

    /**
     * @param resource|string $content
     * @param array{fileName?: string, contentType?: string} $options
     */
    #[Override]
    public function embedContent($content, array $options = []): string
    {
        $name = $options['fileName'] ?? throw new InvalidArgumentException('Embedded content needs a "fileName".');
        $this->email->embed($content, $name, $options['contentType'] ?? null);

        return "cid:$name";
    }

    #[Override]
    public function toString(): string
    {
        return $this->email->toString();
    }

    /**
     * @param Address[] $addresses
     * @return array<string, string>
     */
    private function getAddresses(array $addresses): array
    {
        $strings = [];

        foreach ($addresses as $address) {
            $strings[$address->getAddress()] = $address->getName();
        }

        return $strings;
    }

    /**
     * @param array<int|string, string>|string $addresses
     * @return list<Address>
     */
    private function createAddresses(array|string $addresses): array
    {
        $objects = [];

        foreach ((array)$addresses as $address => $name) {
            $objects[] = is_string($address) ? new Address($address, $name) : new Address($name);
        }

        return $objects;
    }
}
