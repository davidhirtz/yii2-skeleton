<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\FormTextField;
use yii\base\DynamicModel;

class FormTextFieldTest extends TestCase
{
    public function testTheContentIsRenderedAsFormText(): void
    {
        $html = FormTextField::make()
            ->model(new DynamicModel(['duration']))
            ->property('duration')
            ->content('1:01:05')
            ->render();

        self::assertStringContainsString('<div class="label">Duration</div>', $html);
        self::assertStringContainsString('<div class="form-text">1:01:05</div>', $html);
        self::assertStringNotContainsString('<input', $html);
    }

    public function testTheFieldIsHiddenWithoutContent(): void
    {
        foreach ([null, ''] as $content) {
            self::assertSame('', FormTextField::make()
                ->model(new DynamicModel(['duration']))
                ->property('duration')
                ->content($content)
                ->render());
        }
    }

    public function testAFieldsetKeepsTheFieldOfAnUnsafeAttribute(): void
    {
        self::assertTrue(FormTextField::make()->isDisabled());
    }
}
