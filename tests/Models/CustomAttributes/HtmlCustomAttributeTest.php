<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use yii\base\DynamicModel;

class HtmlCustomAttributeTest extends TestCase
{
    public function testHeightSetsTheEditorHeight(): void
    {
        $field = HtmlCustomAttribute::make('text')
            ->height(150)
            ->createField(new DynamicModel(['text']));

        self::assertInstanceOf(TinyMceField::class, $field);
        self::assertSame(150, $field->height);
    }

    public function testNoHeightKeepsTheFieldDefault(): void
    {
        $field = HtmlCustomAttribute::make('text')
            ->height(150)
            ->height(null)
            ->createField(new DynamicModel(['text']));

        self::assertInstanceOf(TinyMceField::class, $field);
        self::assertSame(TinyMceField::make()->height, $field->height);
    }
}
