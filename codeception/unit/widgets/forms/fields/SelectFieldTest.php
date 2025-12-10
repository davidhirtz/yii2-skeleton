<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\widgets\forms\fields;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use yii\base\Model;

class SelectFieldTest extends Unit
{
    public function testDefaultItemsFromModel(): void
    {
        $model = new class () extends Model {
            public int $status = 1;

            /** @noinspection PhpUnused */
            public function getStatuses(): array
            {
                return [
                    0 => 'Inactive',
                    1 => 'Active',
                ];
            }

            public function formName(): string
            {
                return 'F';
            }
        };

        $select = SelectField::make()
            ->model($model)
            ->property('status');

        $html = '<div id="i1-row" class="form-group form-row"><div class="form-label"><label class="label" for="i1">Status</label></div><div class="form-content"><select id="i1" class="input" name="F[status]"><option value="0">Inactive</option><option value="1" selected>Active</option></select></div></div>';
        self::assertEquals($html, $select->render());
    }

    public function testPromptAttributes(): void
    {
        $select = SelectField::make()
            ->prompt(attributes: ['value' => 'default'])
            ->items([
                1 => 'Option 1',
                2 => 'Option 2',
            ]);

        $html = '<div id="i1-row" class="form-group form-row"><div class="form-content"><select id="i1" class="input"><option value="default"></option><option value="1">Option 1</option><option value="2">Option 2</option></select></div></div>';
        self::assertEquals($html, $select->render());
    }

    public function testSingleOption(): void
    {
        $select = SelectField::make()
            ->items([1 => 'Option 1'])
            ->value('1');

        $html = '<input type="hidden" id="i1" value="1">';
        self::assertEquals($html, $select->render());

        $select->showSingleOption();

        $html = '<div class="form-group form-row" data-id="i1"><div class="form-content"><select id="i1" class="input"><option value="1" selected>Option 1</option></select></div></div>';
        self::assertEquals($html, $select->render(true));
    }

}
