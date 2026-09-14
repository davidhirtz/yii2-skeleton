<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Panels;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Stringable;

class InfoListTest extends TestCase
{
    public function testAListWithoutRowsRendersNothing(): void
    {
        self::assertSame('', InfoList::make()->title('Empty')->render());
    }

    public function testARowIsRenderedAsALabelAndAValueCell(): void
    {
        $html = InfoList::make()
            ->title('Info')
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringContainsString('<th scope="row">PHP</th>', $html);
        self::assertStringContainsString('<td>8.5.0</td>', $html);
        self::assertStringContainsString('Info', $html);
    }

    public function testTheActionColumnIsOmittedWhileNoRowHasAnAction(): void
    {
        $html = InfoList::make()
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringNotContainsString('table-action', $html);
    }

    public function testASingleActionAddsTheColumnToEveryRow(): void
    {
        $html = InfoList::make()
            ->addRow('Cache', 'cache', '<button></button>')
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertSame(2, substr_count($html, 'class="table-action"'));
    }

    public function testTheValueHintIsRenderedBelowTheValue(): void
    {
        $html = TestInfoList::make()
            ->addRow('Cache', TestInfoList::make()->value('cache', 'yii\caching\ArrayCache'))
            ->render();

        self::assertStringContainsString('<div>cache</div>', $html);
        self::assertStringContainsString('<div class="small">yii\caching\ArrayCache</div>', $html);
    }

    public function testAClosureCanAddRowsAfterTheDefaults(): void
    {
        $html = InfoList::make()
            ->addRow('PHP', '8.5.0')
            ->rows(fn (InfoList $list) => $list->addRow('Yii', '2.0.55'))
            ->render();

        self::assertStringContainsString('8.5.0', $html);
        self::assertStringContainsString('2.0.55', $html);
    }
}

/**
 * Declared here rather than in a file of its own: PHPUnit only includes files ending in `Test.php`, and the tests
 * are not covered by Composer's autoload map.
 */
class TestInfoList extends InfoList
{
    public function value(string|Stringable $value, string|Stringable|null $hint): string|Stringable
    {
        return $this->getValue($value, $hint);
    }
}
