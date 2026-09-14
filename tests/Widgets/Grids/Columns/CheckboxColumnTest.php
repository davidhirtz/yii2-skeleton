<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Columns;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Grids\Columns\CheckboxColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use yii\base\Model;

/**
 * A checkbox without a name is never submitted, so a column that renders one silently empties every selection the
 * grid's bulk actions read.
 */
class CheckboxColumnTest extends TestCase
{
    public function testTheCheckboxCarriesTheParamAndTheRecordKey(): void
    {
        $column = $this->createColumn();

        $expects = '<td><input type="checkbox" class="input checkbox" name="selection[]" value="12"'
            . ' data-check="multiple"></td>';

        self::assertEquals($expects, (string)$column->renderBody(new CheckboxTestModel(), 12, 0));
    }

    public function testTheParamIsRenamed(): void
    {
        $column = $this->createColumn()
            ->param('fileIds');

        self::assertStringContainsString(
            'name="fileIds[]"',
            (string)$column->renderBody(new CheckboxTestModel(), 12, 0)
        );
    }

    public function testATrailingArraySuffixIsNotDoubled(): void
    {
        $column = $this->createColumn()
            ->param('fileIds[]');

        self::assertStringContainsString(
            'name="fileIds[]"',
            (string)$column->renderBody(new CheckboxTestModel(), 12, 0)
        );
    }

    /**
     * The single-select script pairs the checkboxes by their name, so it must not carry the array suffix.
     */
    public function testASingleSelectionIsNotAnArray(): void
    {
        $column = $this->createColumn()
            ->multiple(false);

        $body = (string)$column->renderBody(new CheckboxTestModel(), 12, 0);

        self::assertStringContainsString('name="selection"', $body);
        self::assertStringContainsString('data-check="single"', $body);
    }

    public function testTheHeaderChecksEveryCheckboxOfItsGrid(): void
    {
        $grid = GridView::make();
        $column = $this->createColumn()->grid($grid);

        self::assertStringContainsString(
            'data-check-all="#' . $grid->getId() . '"',
            (string)$column->renderHeader()
        );
    }

    protected function createColumn(): CheckboxColumn
    {
        return CheckboxColumn::make();
    }
}

class CheckboxTestModel extends Model
{
    public function __construct(public int $id = 12)
    {
        parent::__construct();
    }
}
