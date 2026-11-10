<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\LinkDialog;

class LinkDialogTest extends TestCase
{
    public function testTheFormHoldsTheLinkItEdits(): void
    {
        $html = LinkDialog::make()
            ->href('/kontakt#form')
            ->text('Ågot 東京 🚀')
            ->title('Mu' . "\u{308}" . 'ller')
            ->blank()
            ->render();

        self::assertStringContainsString('id="' . LinkDialog::ID . '"', $html);
        self::assertStringContainsString('data-link-dialog', $html);
        self::assertStringContainsString('hx-boost="false"', $html);
        self::assertStringContainsString('name="href" value="/kontakt#form"', $html);
        self::assertStringContainsString('value="Ågot 東京 🚀"', $html);
        self::assertStringContainsString('value="Mu' . "\u{308}" . 'ller"', $html);
        self::assertMatchesRegularExpression('/name="target" value="_blank"[^>]*checked/', $html);
        self::assertStringNotContainsString('name="class"', $html);
    }

    public function testTheStyleSelectShowsOnlyWithClasses(): void
    {
        $html = LinkDialog::make()
            ->classes(['' => 'None', 'btn' => 'Button'])
            ->cssClass('btn')
            ->render();

        self::assertMatchesRegularExpression('/<option value="btn" selected>Button<\/option>/', $html);
        self::assertDoesNotMatchRegularExpression('/name="target" value="_blank"[^>]*checked/', $html);
    }
}
