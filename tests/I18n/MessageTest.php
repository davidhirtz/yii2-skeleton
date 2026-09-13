<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class MessageTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'en-US';
    }

    public function testAMessageRoundTripsThroughJson(): void
    {
        $message = Message::make('skeleton', 'AUTH_USER_DESCRIPTION');
        $decoded = Message::fromJson($message->toJson());

        self::assertSame('{"category":"skeleton","key":"AUTH_USER_DESCRIPTION"}', $message->toJson());
        self::assertSame('skeleton', $decoded->category);
        self::assertSame('AUTH_USER_DESCRIPTION', $decoded->key);
        self::assertSame([], $decoded->params);
    }

    public function testParamsRoundTripAndAreFormatted(): void
    {
        $message = Message::make('skeleton', 'TRAIL_WAS_DELETED', ['model' => 'Entry']);
        $decoded = Message::fromJson($message->toJson());

        self::assertSame(['model' => 'Entry'], $decoded->params);
        self::assertSame('Entry was deleted', (string)$decoded);
    }

    public function testWithParamsLeavesTheOriginalAlone(): void
    {
        $message = Message::make('skeleton', 'TRAIL_WAS_DELETED');

        self::assertSame('Entry was deleted', (string)$message->withParams(['model' => 'Entry']));
        self::assertSame([], $message->params);
    }

    public function testAMessageIsTranslatedInTheCurrentLanguage(): void
    {
        $message = Message::make('skeleton', 'AUTH_USER_DESCRIPTION');

        self::assertSame('Manage users', (string)$message);

        Yii::$app->language = 'de';
        self::assertSame('Benutzer verwalten', (string)$message);
    }

    public function testALiteralIsReturnedForTextThatIsNoPointer(): void
    {
        $message = Message::fromJson('Update entries');

        self::assertTrue($message->isLiteral());
        self::assertSame('Update entries', (string)$message);

        Yii::$app->language = 'de';
        self::assertSame('Update entries', (string)$message);
    }

    public function testJsonThatIsNoPointerIsLiteralToo(): void
    {
        self::assertSame('[1,2]', (string)Message::fromJson('[1,2]'));
        self::assertSame('42', (string)Message::fromJson('42'));
    }

    public function testNothingIsNull(): void
    {
        self::assertNull(Message::fromJson(null));
        self::assertNull(Message::fromJson(''));
    }
}
