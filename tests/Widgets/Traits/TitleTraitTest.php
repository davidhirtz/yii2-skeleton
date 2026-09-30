<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Traits;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Yii;

/**
 * A title is stored as given and encoded once, where it is rendered.
 */
class TitleTraitTest extends TestCase
{
    private const string TITLE = 'Bücher & Theken';
    private const string ENCODED = 'Bücher &amp; Theken';

    public function testTheHeaderEncodesTheTitleOnce(): void
    {
        $html = Header::make()
            ->title(self::TITLE)
            ->render();

        self::assertStringContainsString('<h1>' . self::ENCODED . '</h1>', $html);
    }

    public function testTheLayoutEncodesTheViewTitleOnce(): void
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        $html = $view->render('@skeleton/../resources/tests/views/layouts/main.php', [
            'content' => Header::make()->title(self::TITLE)->render(),
        ]);

        self::assertSame(self::TITLE, $view->title);
        self::assertStringContainsString('<title>' . self::ENCODED . '</title>', $html);
        self::assertStringContainsString('<h1>' . self::ENCODED . '</h1>', $html);
    }

    public function testTheModalEncodesTheTitleOnce(): void
    {
        $html = Modal::make()
            ->title(self::TITLE)
            ->render();

        self::assertStringContainsString('<div class="modal-title">' . self::ENCODED . '</div>', $html);
    }

    public function testTheDeleteButtonHandsTheTitleToItsModalOnce(): void
    {
        $html = DeleteButton::make()
            ->url('/admin/test/delete')
            ->title(self::TITLE)
            ->render();

        self::assertStringContainsString(self::ENCODED, $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testTheCardEncodesTheTitleOnceCollapsibleOrNot(): void
    {
        foreach ([null, false] as $collapsed) {
            $html = Card::make()
                ->title(self::TITLE)
                ->collapsed($collapsed)
                ->content('Content')
                ->render();

            self::assertStringContainsString(self::ENCODED, $html);
            self::assertStringNotContainsString('&amp;amp;', $html);
        }
    }

    public function testThePanelHandsTheTitleToItsCardOnce(): void
    {
        $html = Panel::make()
            ->title(self::TITLE)
            ->content('Content')
            ->render();

        self::assertStringContainsString(self::ENCODED, $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testTheErrorSummaryEncodesTheTitleOnce(): void
    {
        $html = ErrorSummary::make()
            ->title(self::TITLE)
            ->errors(['Error'])
            ->render();

        self::assertStringContainsString('<div class="alert-heading">' . self::ENCODED . '</div>', $html);
    }

    public function testTheColumnEncodesTheTitleOnce(): void
    {
        $header = (string)Column::make()
            ->title(self::TITLE)
            ->renderHeader();

        self::assertStringContainsString('>' . self::ENCODED . '</th>', $header);
    }

    public function testAStringableTitleIsRenderedAsItIs(): void
    {
        $html = Header::make()
            ->title(Span::make()->text(self::TITLE))
            ->render();

        self::assertStringContainsString('<h1><span>' . self::ENCODED . '</span></h1>', $html);
    }
}
