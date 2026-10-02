<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mail;

use LogicException;
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
     * A message composed from an HTML view alone gets its text part derived here rather than by Yii, which strips
     * every tag and with it the target of every link: the text part of a mail whose point is its button would carry
     * no URL at all.
     *
     * @param array<string, mixed> $params
     */
    #[Override]
    public function compose($view = null, array $params = []): Message
    {
        /** @var Message $message */
        $message = parent::compose($view, $params);

        if ($view !== null && (!is_array($view) || !isset($view['text']))) {
            $html = $message->email->getHtmlBody();

            if (is_string($html)) {
                $message->setTextBody(static::createTextBody($html));
            }
        }

        return $message;
    }

    /**
     * Yii's derivation of a text part, with every link's target kept beside its label.
     */
    public static function createTextBody(string $html): string
    {
        if (preg_match('~<body[^>]*>(.*?)</body>~is', $html, $match)) {
            $html = $match[1];
        }

        $html = (string)preg_replace('~<((style|script))[^>]*>(.*?)</\1>~is', '', $html);
        $charset = Yii::$app->charset;

        $html = (string)preg_replace_callback('~<a\s[^>]*?href=(["\'])(.*?)\1[^>]*>(.*?)</a>~is', function (array $match) use ($charset): string {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, $charset);
            $label = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, $charset));

            if ($label === '') {
                return htmlspecialchars($url, ENT_NOQUOTES, $charset);
            }

            if (str_starts_with($url, 'mailto:') || str_starts_with($url, '#') || str_contains($label, $url)) {
                return $match[3];
            }

            return $match[3] . ': ' . htmlspecialchars($url, ENT_NOQUOTES, $charset);
        }, $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, $charset);
        $text = (string)preg_replace("~^[ \t]+~m", '', trim($text));

        return (string)preg_replace('~\R\R+~mu', "\n\n", $text);
    }

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

    /**
     * The transport's DSN without its credentials or options, for what an installation reports about itself.
     */
    public function getMaskedTransportDsn(): ?string
    {
        if ($this->transport instanceof TransportInterface) {
            return (string)$this->transport;
        }

        if ($this->transport === null) {
            return null;
        }

        // up to the last `@`, so that one left unencoded in a password cannot leave the rest of it behind
        return preg_replace(['#(?<=://)[^\s()]*@#', '#\?[^\s()]*#'], '', $this->transport);
    }

    /**
     * Why no transport can be built, without sending anything: none configured, a malformed DSN, a bridge that is
     * not installed, or a bridge missing its own dependency (`symfony/http-client` for the HTTP API ones).
     */
    public function getTransportError(): ?string
    {
        try {
            $this->getTransport();
        } catch (InvalidConfigException|LogicException $exception) {
            return $exception->getMessage();
        }

        return null;
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
