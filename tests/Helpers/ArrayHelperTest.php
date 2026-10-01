<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Test\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testReplaceValueReplacesTheFirstStrictMatch(): void
    {
        $array = ['a', '1', 1, 'a'];
        ArrayHelper::replaceValue($array, '1', 'one');
        ArrayHelper::replaceValue($array, 'missing', 'x');

        self::assertSame(['a', 'one', 1, 'a'], $array);
    }

    public function testSetDefaultValuesKeepsWhatIsSet(): void
    {
        $array = ['name' => 'Å', 'empty' => null];
        ArrayHelper::setDefaultValues($array, ['name' => 'B', 'empty' => 'C', 'new' => '東京']);

        self::assertSame(['name' => 'Å', 'empty' => null, 'new' => '東京'], $array);
    }
}
