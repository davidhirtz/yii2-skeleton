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

    public function testARowIsRenderedWithTheFormRowClasses(): void
    {
        $html = InfoList::make()
            ->title('Info')
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringContainsString('class="form-rows"', $html);
        self::assertStringContainsString('class="form-group form-row"', $html);
        self::assertStringContainsString('<div class="form-label">PHP</div>', $html);
        self::assertStringContainsString('<div class="form-content">8.5.0</div>', $html);
        self::assertStringContainsString('Info', $html);
    }

    public function testARowWithoutAnActionIsNotWrapped(): void
    {
        $html = InfoList::make()
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringNotContainsString('form-action', $html);
    }

    public function testAnActionIsPlacedBesideTheValue(): void
    {
        $html = InfoList::make()
            ->addRow('Cache', 'cache', '<button></button>')
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertSame(1, substr_count($html, 'class="form-action"'));
        self::assertStringContainsString('<div class="form-action"><div>cache</div><button></button></div>', $html);
    }

    public function testTheValueHintIsRenderedBelowTheValue(): void
    {
        $html = TestInfoList::make()
            ->addRow('Cache', TestInfoList::make()->value('cache', 'yii\caching\ArrayCache'))
            ->render();

        self::assertStringContainsString('<div>cache</div>', $html);
        self::assertStringContainsString('<div class="form-hint">yii\caching\ArrayCache</div>', $html);
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
