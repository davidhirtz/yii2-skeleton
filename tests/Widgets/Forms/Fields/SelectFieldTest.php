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
            /**
             * @return array<int|string, mixed>
             */
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

    /**
     * An option without a `value` posts its label, so a prompt carrying one has to spell the empty value out.
     */
    public function testPromptValue(): void
    {
        $select = SelectField::make()
            ->prompt('Detect from browser')
            ->items([1 => 'Option 1', 2 => 'Option 2']);

        self::assertStringContainsString('<option value="">Detect from browser</option>', $select->render());
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

    /**
     * An unselected option posts nothing, so the hidden input is what empties the list.
     */
    public function testMultiple(): void
    {
        $select = SelectField::make()
            ->name('F[languages]')
            ->items(['de' => 'Deutsch', 'fr' => 'Français'])
            ->value(['fr'])
            ->multiple();

        $html = $select->render();

        self::assertStringContainsString('<input type="hidden" name="F[languages]" value=""><select', $html);
        self::assertStringContainsString('name="F[languages][]" multiple data-multiselect="Remove {label}"', $html);
        self::assertStringContainsString('<option value="fr" selected>Français</option>', $html);
    }

    public function testDisabledMultipleHasNoHiddenInput(): void
    {
        $select = SelectField::make()
            ->name('F[languages]')
            ->items(['de' => 'Deutsch'])
            ->attribute('disabled', true)
            ->multiple();

        self::assertStringNotContainsString('type="hidden"', $select->render());
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

    public function testAnItemWithItemsIsAnOptionGroup(): void
    {
        $select = SelectField::make()
            ->items([
                'a' => ['label' => 'Héroes & Å', 'items' => [3 => 'Москва.jpg', 4 => ['label' => '東京.png', 'data-x' => 'y']]],
                5 => 'Loose',
            ])
            ->value('4');

        $html = $select->render();

        self::assertStringContainsString('<optgroup label="Héroes &amp; Å"><option value="3">Москва.jpg</option><option value="4" selected data-x="y">東京.png</option></optgroup><option value="5">Loose</option>', $html);
    }

    /**
     * A group holds options, so a required group of two is a choice and not a hidden input.
     */
    public function testAnOptionGroupCountsItsOptions(): void
    {
        $select = SelectField::make()
            ->attribute('required', true)
            ->items(['a' => ['label' => 'Heroes', 'items' => [3 => 'A', 4 => 'B']]]);

        self::assertStringContainsString('<select', $select->render());
    }
}
