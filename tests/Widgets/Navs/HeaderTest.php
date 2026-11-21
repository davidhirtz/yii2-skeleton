<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Navs\Header;

class HeaderTest extends TestCase
{
    /**
     * On the first page too, so that a grid swap to the second finds the header to replace.
     */
    public function testAPaginatedHeaderHasALiteralIdOnEveryPage(): void
    {
        foreach ([1, 2] as $page) {
            $html = Header::make()
                ->title('Logins')
                ->pagination($page)
                ->render();

            self::assertStringContainsString('id="' . Header::PAGINATED_ID . '"', $html, "Page $page");
        }

        self::assertStringContainsString('<span class="header-subtitle-item">', $html);
    }

    public function testAHeaderWithoutPaginationHasNoId(): void
    {
        $html = Header::make()
            ->title('Logins')
            ->render();

        self::assertStringNotContainsString(Header::PAGINATED_ID, $html);
    }

    public function testAnIdOfItsOwnIsKept(): void
    {
        $html = Header::make()
            ->attributes(['id' => 'own'])
            ->title('Logins')
            ->pagination(2)
            ->render();

        self::assertStringContainsString('id="own"', $html);
        self::assertStringNotContainsString(Header::PAGINATED_ID, $html);
    }
}
