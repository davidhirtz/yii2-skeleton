<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use yii\base\DynamicModel;

/**
 * A screen reader announces a field's error and hint only when the control names them.
 */
class FieldDescriptionTest extends TestCase
{
    public function testTheControlNamesItsErrorAndHint(): void
    {
        $model = new DynamicModel(['name' => '']);
        $model->addError('name', 'Name cannot be blank.');

        $content = InputField::make()
            ->model($model)
            ->property('name')
            ->hint('Shown in the menu.')
            ->render();

        self::assertMatchesRegularExpression('/<input[^>]+aria-describedby="([\w-]+)-error \1-hint"/', $content);
        self::assertStringContainsString('aria-invalid="true"', $content);
        self::assertMatchesRegularExpression('/<div id="[\w-]+-error" class="form-error">Name cannot be blank\./', $content);
        self::assertMatchesRegularExpression('/<div id="[\w-]+-hint" class="form-hint">Shown in the menu\./', $content);
    }

    public function testAHintHoldingACountCountsTheCharacters(): void
    {
        $content = InputField::make()
            ->model(new DynamicModel(['description' => 'Grüße']))
            ->property('description')
            ->hint('At most 160 <characters>, now {count}.')
            ->render();

        self::assertStringContainsString('data-character-counter', $content);
        self::assertStringContainsString('At most 160 &lt;characters&gt;, now <span data-character-count>5</span>.', $content);
    }

    public function testAControlWithNeitherNamesNothing(): void
    {
        $content = InputField::make()
            ->model(new DynamicModel(['name' => '']))
            ->property('name')
            ->render();

        self::assertStringNotContainsString('aria-describedby', $content);
    }

    public function testACheckboxNamesItsHintToo(): void
    {
        $content = CheckboxField::make()
            ->model(new DynamicModel(['consent' => '']))
            ->property('consent')
            ->hint('We will not share it.')
            ->render();

        self::assertMatchesRegularExpression('/<input[^>]+aria-describedby="[\w-]+-hint"/', $content);
    }
}
