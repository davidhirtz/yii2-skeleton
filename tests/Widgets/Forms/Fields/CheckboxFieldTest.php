<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use yii\base\Model;

class CheckboxFieldTest extends TestCase
{
    /**
     * What the record holds and what the box posts are two different things in the one `value` attribute, so
     * naming the second must not answer the first — `checkedValue()` assigned the attribute the tick is read
     * from and every field that called it rendered `checked` whatever the record said.
     */
    public function testTheCheckedValueDoesNotDecideTheTick(): void
    {
        $content = CheckboxField::make()
            ->model($this->createModel('no'))
            ->property('consent')
            ->checkedValue('yes')
            ->render();

        self::assertStringContainsString('value="yes"', $content);
        self::assertStringNotContainsString('checked', $content);

        $content = CheckboxField::make()
            ->model($this->createModel('yes'))
            ->property('consent')
            ->checkedValue('yes')
            ->render();

        self::assertStringContainsString('value="yes" checked', $content);
    }

    /**
     * An explicitly assigned value still overrides the record's, which is what it is for.
     */
    public function testAnAssignedValueStillDecidesTheTick(): void
    {
        $content = CheckboxField::make()
            ->model($this->createModel('no'))
            ->property('consent')
            ->checkedValue('yes')
            ->value('yes')
            ->render();

        self::assertStringContainsString('value="yes" checked', $content);
    }

    public function testTheUncheckedValueRendersTheHiddenInputInFrontOfTheBox(): void
    {
        $content = CheckboxField::make()
            ->model($this->createModel('no'))
            ->property('consent')
            ->checkedValue('yes')
            ->uncheckedValue('no')
            ->render();

        self::assertStringContainsString(
            '<input type="hidden" name="F[consent]" value="no"><input type="checkbox"',
            $content,
        );
    }

    private function createModel(string $consent): Model
    {
        return new class ($consent) extends Model {
            public function __construct(public string $consent)
            {
                parent::__construct();
            }

            public function formName(): string
            {
                return 'F';
            }
        };
    }
}
