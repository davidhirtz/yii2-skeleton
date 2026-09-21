<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\LanguageDropdownButton;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class LanguageDropdownButtonTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->getAdminModule()->languages = ['en-US', 'de'];
    }

    public function testASingleLanguageRendersNothing(): void
    {
        $this->getAdminModule()->languages = ['en-US'];

        self::assertSame('', LanguageDropdownButton::make()->render());
    }

    public function testTheDropdownOffersEveryAdminLanguage(): void
    {
        $content = LanguageDropdownButton::make()->render();

        self::assertStringContainsString('hx-vals="{&quot;language&quot;:&quot;en-US&quot;}"', $content);
        self::assertStringContainsString('hx-vals="{&quot;language&quot;:&quot;de&quot;}"', $content);
    }

    public function testTheCurrentLanguageIsTheSelectedOption(): void
    {
        Yii::$app->language = 'de';

        $content = LanguageDropdownButton::make()->render();

        self::assertStringContainsString('class="dropdown-option i18n-dropdown-option selected"', $content);
        self::assertSame(1, substr_count($content, 'selected'));
    }

    private function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}
