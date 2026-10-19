<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\I18n\Formatter;
use Hirtz\Skeleton\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;

class FormatterTest extends TestCase
{
    public function testTheApplicationUsesTheFormatter(): void
    {
        self::assertInstanceOf(Formatter::class, Yii::$app->getFormatter());
    }

    #[DataProvider('shortSizeDataProvider')]
    public function testAShortSizeIsWholeBelowAMegabyte(int $bytes, string $expected): void
    {
        $formatter = Yii::$app->getFormatter();
        $formatter->language = 'en-US';

        self::assertSame($expected, $formatter->asShortSize($bytes));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function shortSizeDataProvider(): array
    {
        return [
            'bytes' => [512, '512 B'],
            'kilobytes' => [242516, '243 kB'],
            'megabytes' => [4_200_000, '4.2 MB'],
            'gigabytes' => [1_500_000_000, '1.5 GB'],
        ];
    }

    public function testAnExplicitPrecisionIsKept(): void
    {
        $formatter = Yii::$app->getFormatter();
        $formatter->language = 'en-US';

        self::assertSame('242.52 kB', $formatter->asShortSize(242516, 2));
    }
}
