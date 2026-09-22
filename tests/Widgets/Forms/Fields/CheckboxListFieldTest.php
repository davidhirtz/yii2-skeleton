<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxListField;
use yii\base\Model;

class CheckboxListFieldTest extends TestCase
{
    public function testTheModelValueChecksTheItems(): void
    {
        $field = CheckboxListField::make()
            ->model($this->createModel())
            ->property('tags')
            ->items([
                1 => 'First',
                2 => 'Second',
            ]);

        $html = '<div class="form-group form-row" data-id="f-tags">'
            . '<div class="form-label"><div class="label">Tags</div></div>'
            . '<div class="form-content">'
            . '<input type="hidden" name="F[tags]" value="">'
            . '<div class="form-checkbox"><div class="checkbox"><input type="checkbox" id="f-tags-1" class="input" name="F[tags][]" value="1"></div><label class="label" for="f-tags-1">First</label></div>'
            . '<div class="form-checkbox"><div class="checkbox"><input type="checkbox" id="f-tags-2" class="input" name="F[tags][]" value="2" checked></div><label class="label" for="f-tags-2">Second</label></div>'
            . '</div></div>';

        self::assertEquals($html, $field->render());
    }

    /**
     * The group's caption labels no single input, so it is a `div` rather than a `<label>` whose `for` would
     * point at an id that is never rendered — every item carries a label of its own.
     */
    public function testTheGroupLabelClaimsNoInput(): void
    {
        $content = CheckboxListField::make()
            ->label('Tags')
            ->addItem(1, 'First')
            ->render();

        self::assertStringContainsString('<div class="label">Tags</div>', $content);
    }

    public function testAnItemCarriesItsOwnLabelAttributes(): void
    {
        $content = CheckboxListField::make()
            ->items([1 => 'First', 2 => 'Second'])
            ->itemAttributes([2 => ['class' => 'text-invalid']])
            ->render();

        self::assertStringContainsString('<label class="text-invalid label" for="-2">Second</label>', $content);
        self::assertSame(1, substr_count($content, 'text-invalid'));
    }

    /**
     * A checkbox that is not checked posts nothing at all, so without the hidden input "nothing checked" would
     * never reach the model and the previous value would stand.
     */
    public function testTheHiddenInputIsAlwaysRendered(): void
    {
        $content = CheckboxListField::make()
            ->model($this->createModel())
            ->property('tags')
            ->render();

        self::assertStringContainsString('<input type="hidden" name="F[tags]" value="">', $content);
    }

    private function createModel(): Model
    {
        return new class () extends Model {
            /** @var list<int> */
            public array $tags = [2];

            public function formName(): string
            {
                return 'F';
            }

            /**
             * @return array<string, string>
             */
            public function attributeLabels(): array
            {
                return ['tags' => 'Tags'];
            }
        };
    }
}
