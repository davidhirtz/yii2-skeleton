<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\EnvironmentAlert;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Buttons\AdminButton;
use Hirtz\Skeleton\Widgets\Navs\Breadcrumbs;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;

class RequestEnvironmentTest extends TestCase
{
    public function testTheTestHostIsLocal(): void
    {
        $request = $this->getRequest();

        self::assertSame(Request::ENVIRONMENT_LOCAL, $request->getEnvironment());
        self::assertSame('Local', $request->getEnvironmentName());
    }

    #[DataProvider('hostDataProvider')]
    public function testTheEnvironmentIsMatchedAgainstTheHost(string $host, ?string $environment): void
    {
        $request = $this->getRequest();
        $request->setHostInfo("https://$host");

        self::assertSame($environment, $request->getEnvironment());
    }

    /**
     * @return list<array{string, string|null}>
     */
    public static function hostDataProvider(): array
    {
        return [
            ['localhost', Request::ENVIRONMENT_LOCAL],
            ['www.example.localhost', Request::ENVIRONMENT_LOCAL],
            ['stage.example.com', Request::ENVIRONMENT_STAGE],
            ['www.stage.example.com', Request::ENVIRONMENT_STAGE],
            ['www.example.com', null],
            ['example.com', null],
            ['localhost.example.com', null],
        ];
    }

    public function testAConfiguredEnvironmentIsLabelledWithItsKey(): void
    {
        $request = $this->getRequest();
        $request->environments = ['preview' => ['preview.*']];
        $request->setHostInfo('https://preview.example.com');

        self::assertSame('preview', $request->getEnvironment());
        self::assertSame('preview', $request->getEnvironmentName());
    }

    public function testTheBreadcrumbNamesTheEnvironment(): void
    {
        $html = $this->renderBreadcrumbs();

        self::assertStringContainsString('Test (Local)', $html);
    }

    public function testTheBreadcrumbLeavesTheNameAloneOnAProductionHost(): void
    {
        $this->getRequest()->setHostInfo('https://www.example.com');

        $html = $this->renderBreadcrumbs();

        self::assertStringContainsString('Test', $html);
        self::assertStringNotContainsString('Test (', $html);
    }

    public function testTheAdminButtonIsBadged(): void
    {
        $html = $this->renderAdminButton();

        self::assertStringContainsString('admin-btn-badge', $html);
        self::assertStringContainsString('>Local<', $html);
    }

    public function testTheAdminButtonCarriesNoBadgeOnAProductionHost(): void
    {
        $this->getRequest()->setHostInfo('https://www.example.com');

        self::assertStringNotContainsString('admin-btn-badge', $this->renderAdminButton());
    }

    public function testTheAlertNamesTheEnvironmentAndTheHost(): void
    {
        $html = (string)EnvironmentAlert::make();

        self::assertStringContainsString('Local', $html);
        self::assertStringContainsString('www.test.localhost', $html);
    }

    public function testTheAlertIsEmptyOnAProductionHost(): void
    {
        $this->getRequest()->setHostInfo('https://www.example.com');

        self::assertSame('', (string)EnvironmentAlert::make());
    }

    /**
     * The button is invisible to a guest, which `Tests\Widgets\Buttons\AdminButtonTest` is about — the badge is
     * what is under test here, so this one opts out rather than logging in.
     */
    private function renderAdminButton(): string
    {
        return AdminButton::make()
            ->roles([User::ROLE_ANY])
            ->render();
    }

    private function renderBreadcrumbs(): string
    {
        Yii::$app->name = 'Test';
        Yii::$app->controller = new Controller('test', Yii::$app);

        return (string)Breadcrumbs::make()->addBreadcrumb('Page');
    }

    private function getRequest(): Request
    {
        return $this->getWebRequest();
    }
}
