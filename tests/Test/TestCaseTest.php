<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Test;

use Hirtz\Skeleton\Test\TestCase;
use yii\data\ArrayDataProvider;

/**
 * @see https://github.com/davidhirtz/yii2-monorepo/issues/470
 */
class TestCaseTest extends TestCase
{
    public function testEveryApplicationStartsCountingDataProvidersAtZero(): void
    {
        new ArrayDataProvider();
        self::assertSame('dp-1', (new ArrayDataProvider())->id);

        $this->reloadApplication();

        self::assertNull((new ArrayDataProvider())->id);
    }
}
