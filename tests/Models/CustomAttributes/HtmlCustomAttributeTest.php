<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use yii\base\DynamicModel;

class HtmlCustomAttributeTest extends TestCase
{
    public function testFieldConfiguresTheEditor(): void
    {
        $field = HtmlCustomAttribute::make('text')
            ->field(fn (TinyMceField $field) => $field->height = 150)
            ->createField(new DynamicModel(['text']));

        self::assertInstanceOf(TinyMceField::class, $field);
        $field->render();

        self::assertSame(150, $field->height);
    }

    public function testNoFieldKeepsTheEditorDefault(): void
    {
        $field = HtmlCustomAttribute::make('text')
            ->field(fn (TinyMceField $field) => $field->height = 150)
            ->field(null)
            ->createField(new DynamicModel(['text']));

        self::assertInstanceOf(TinyMceField::class, $field);
        $field->render();

        self::assertSame(TinyMceField::make()->height, $field->height);
    }
}
