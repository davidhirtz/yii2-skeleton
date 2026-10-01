<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\StringHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class StringHelperTest extends TestCase
{
    public function testAListReadsAsAnEnumeration(): void
    {
        self::assertSame('', StringHelper::enumerate([]));
        self::assertSame('DE', StringHelper::enumerate(['DE']));
        self::assertSame('DE and FR', StringHelper::enumerate(['DE', 'FR']));
        self::assertSame('DE, FR and PT', StringHelper::enumerate(['DE', 'FR', 'PT']));

        Yii::$app->language = 'de';

        self::assertSame('DE und FR', StringHelper::enumerate(['DE', 'FR']));
    }

    public function testAnEmailIsObfuscated(): void
    {
        self::assertSame('ma**@ex*****.com', StringHelper::obfuscateEmail('mail@example.com'));
        self::assertSame('ma**@example.com', StringHelper::obfuscateEmail('mail@example.com', false));
        self::assertSame('a@ex.co.uk', StringHelper::obfuscateEmail('a@ex.co.uk', true, 2, 0));
    }

    public function testObfuscationCountsCharactersNotBytes(): void
    {
        self::assertSame('Mü****@東京**.jp', StringHelper::obfuscateEmail('Müller@東京都庁.jp'));
        self::assertSame('Å*', StringHelper::obfuscateText('Åą', 1));
    }
}
