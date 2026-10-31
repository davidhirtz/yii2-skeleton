<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use yii\base\Model;

class CheckboxFieldTest extends TestCase
{
    /**
     * The record's value and the posted value share the `value` attribute, so `checkedValue()` must not set the tick.
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

            #[\Override]
            public function formName(): string
            {
                return 'F';
            }
        };
    }
}
