<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;

class DeleteGridButtonTest extends TestCase
{
    /**
     * The button is an icon alone, so what it deletes is said by its tooltip — and by its `aria-label`, since
     * `includes/tooltips.ts` takes the `title` off the element.
     */
    public function testTheLabelIsTheTooltip(): void
    {
        $html = DeleteGridButton::make()
            ->label('Delete redirect')
            ->url(['/admin/redirect/delete', 'id' => 1])
            ->render();

        self::assertStringContainsString('aria-label="Delete redirect"', $html);
        self::assertStringContainsString('title="Delete redirect"', $html);
        self::assertStringContainsString('data-tooltip', $html);
    }
}
