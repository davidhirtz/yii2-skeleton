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
}
