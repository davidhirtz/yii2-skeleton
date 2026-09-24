<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mail;

use Hirtz\Skeleton\Mail\Message;
use Hirtz\Skeleton\Test\TestCase;
use Symfony\Component\Mime\Part\DataPart;
use yii\base\InvalidArgumentException;

class MessageTest extends TestCase
{
    public function testAddressesAreReadBackKeyedByAddress(): void
    {
        $message = (new Message())
            ->setFrom('sender@example.com')
            ->setTo(['a@example.com', 'b@example.com' => 'B'])
            ->setReplyTo(['reply@example.com' => 'Reply'])
            ->setCc('cc@example.com')
            ->setBcc(['bcc@example.com']);

        self::assertSame(['sender@example.com' => ''], $message->getFrom());
        self::assertSame(['a@example.com' => '', 'b@example.com' => 'B'], $message->getTo());
        self::assertSame(['reply@example.com' => 'Reply'], $message->getReplyTo());
        self::assertSame(['cc@example.com' => ''], $message->getCc());
        self::assertSame(['bcc@example.com' => ''], $message->getBcc());
    }

    public function testAnUnsetAddressIsEmpty(): void
    {
        self::assertSame([], (new Message())->getTo());
        self::assertSame('', (new Message())->getSubject());
    }

    public function testTheCharsetAppliesToTheBodiesSetAfterIt(): void
    {
        $message = (new Message())
            ->setCharset('iso-8859-1')
            ->setTextBody('Text')
            ->setHtmlBody('<p>Html</p>');

        self::assertSame('iso-8859-1', $message->getCharset());
        self::assertSame('Text', $message->email->getTextBody());
        self::assertSame('iso-8859-1', $message->email->getTextCharset());
        self::assertSame('iso-8859-1', $message->email->getHtmlCharset());
    }

    public function testAttachmentsAreNamedAfterTheFile(): void
    {
        $message = (new Message())->attach(__FILE__);
        $attachments = $message->email->getAttachments();

        self::assertCount(1, $attachments);
        self::assertSame(basename(__FILE__), $attachments[0]->getFilename());
    }

    public function testAttachedContentKeepsItsOptions(): void
    {
        $message = (new Message())->attachContent('a,b', ['fileName' => 'data.csv', 'contentType' => 'text/csv']);
        $attachment = $message->email->getAttachments()[0] ?? self::fail('No attachment.');

        self::assertSame('data.csv', $attachment->getFilename());
        self::assertSame('text/csv', $attachment->getContentType());
    }

    public function testEmbeddedContentAnswersItsCid(): void
    {
        $message = new Message();
        $cid = $message->embedContent('GIF89a', ['fileName' => 'logo.gif', 'contentType' => 'image/gif']);
        $message->setFrom('sender@example.com')->setTo('a@example.com')->setHtmlBody("<img src=\"$cid\">");

        self::assertSame('cid:logo.gif', $cid);

        $mime = $message->toString();
        $part = $message->email->getAttachments()[0] ?? self::fail('No attachment.');

        self::assertInstanceOf(DataPart::class, $part);
        self::assertTrue($part->hasContentId());
        self::assertStringContainsString("Content-ID: <{$part->getContentId()}>", $mime);
        self::assertStringContainsString("cid:{$part->getContentId()}", $mime);
    }

    public function testEmbeddedContentNeedsAFileName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Message())->embedContent('GIF89a');
    }

    public function testACloneOwnsItsEmail(): void
    {
        $message = (new Message())->setSubject('Original');
        $clone = clone $message;
        $clone->setSubject('Clone');

        self::assertSame('Original', $message->getSubject());
    }

    public function testTheMessageRendersAsMime(): void
    {
        $mime = (new Message())
            ->setFrom(['sender@example.com' => 'Sender'])
            ->setTo('recipient@example.com')
            ->setSubject('Subject')
            ->setTextBody('Body')
            ->toString();

        self::assertStringContainsString('From: Sender <sender@example.com>', $mime);
        self::assertStringContainsString('Subject: Subject', $mime);
    }
}
