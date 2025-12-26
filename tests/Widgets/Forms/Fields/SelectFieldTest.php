<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use yii\base\Model;

class SelectFieldTest extends TestCase
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

        $html = '<div class="form-group form-row" data-id="f-status"><div class="form-label"><label class="label" for="f-status">Status</label></div><div class="form-content"><select id="f-status" class="input" name="F[status]"><option value="0">Inactive</option><option value="1" selected>Active</option></select></div></div>';
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

        $html = '<div class="form-group form-row" data-id="i1"><div class="form-content"><select id="i1" class="input"><option value="default"></option><option value="1">Option 1</option><option value="2">Option 2</option></select></div></div>';
        self::assertEquals($html, $select->render());
    }

    public function testSingleOption(): void
    {
        $select = SelectField::make()
            ->attribute('required', true)
            ->items([1 => 'Option 1'])
            ->value('1');

        $html = '<input type="hidden" id="i1" value="1" required>';
        self::assertEquals($html, $select->render());

        $select->attribute('required', false);

        $html = '<div class="form-group form-row" data-id="i1"><div class="form-content"><select id="i1" class="input"><option value="1" selected>Option 1</option></select></div></div>';
        self::assertEquals($html, $select->render(true));
    }

}
