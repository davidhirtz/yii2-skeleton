<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Consent;

use Hirtz\Skeleton\Consent\Categories\AnalyticsCategory;
use Hirtz\Skeleton\Consent\Categories\ExternalCategory;
use Hirtz\Skeleton\Consent\Categories\MarketingCategory;
use Hirtz\Skeleton\Consent\Categories\RequiredCategory;
use Hirtz\Skeleton\Consent\Category;
use Hirtz\Skeleton\Consent\ConsentManager;
use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Hirtz\Skeleton\Consent\Services\GoogleAnalytics;
use Hirtz\Skeleton\Consent\Services\YouTube;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ConsentManagerTest extends TestCase
{
    public function testTheComponentIsRegistered(): void
    {
        self::assertInstanceOf(ConsentManager::class, ConsentManager::current());
    }

    public function testAnalyticsIsLeftOutWithoutATagId(): void
    {
        self::assertSame(['required', 'external'], $this->getCategoryIds(new ConsentManager()));
    }

    public function testATagManagerContainerListsTagManagerAndAnalytics(): void
    {
        $consent = new ConsentManager(['tagId' => 'GTM-TEST']);
        $analytics = $consent->getCategories()[1];

        self::assertSame(AnalyticsCategory::ID, $analytics->getId());
        self::assertSame(['Google Tag Manager', 'Google Analytics'], $this->getServiceNames($analytics));
        self::assertSame(['_ga', '_ga_*'], $analytics->getFirstPartyCookieNames());
    }

    public function testTheTagIdDefaultsToTheParam(): void
    {
        Yii::$app->params['gtagId'] = 'G-TEST';

        $consent = new ConsentManager();

        self::assertSame('G-TEST', $consent->tagId);
        self::assertSame(['Google Analytics'], $this->getServiceNames($consent->getCategories()[1]));
    }

    public function testConfiguredCategoriesReplaceTheDefaults(): void
    {
        $consent = new ConsentManager([
            'categories' => fn (): array => [
                RequiredCategory::make(),
                MarketingCategory::make(),
                ExternalCategory::make()->services(
                    Service::make()
                        ->name('Calendly')
                        ->provider('Calendly LLC')
                        ->cookies(Cookie::make()->name('__cf_bm')->duration('PT30M')->thirdParty()),
                ),
            ],
        ]);

        self::assertSame(['required', 'external'], $this->getCategoryIds($consent));
        self::assertSame(['Calendly'], $this->getServiceNames($consent->getCategories()[1]));
    }

    public function testAServiceIsConfiguredThroughTheContainer(): void
    {
        Yii::$container->set(YouTube::class, ['purpose' => 'Plays the showreel.']);

        try {
            self::assertSame('Plays the showreel.', YouTube::make()->getPurpose());
            self::assertSame('YouTube', YouTube::make()->getName());
        } finally {
            Yii::$container->clear(YouTube::class);
        }
    }

    public function testTheVersionChangesWithTheCookiesButNotWithTheTexts(): void
    {
        $version = (new ConsentManager())->getVersion();

        self::assertMatchesRegularExpression('/^1-[0-9a-f]{8}$/', $version);

        $reworded = new ConsentManager([
            'categories' => fn (): array => [
                RequiredCategory::make()->title('Essential')->description('Reworded.'),
                ExternalCategory::make()->services(
                    YouTube::make()->purpose('Reworded.'),
                ),
            ],
        ]);

        self::assertSame($version, $reworded->getVersion());

        $added = new ConsentManager(['tagId' => 'G-TEST']);
        self::assertNotSame($version, $added->getVersion());

        $raised = new ConsentManager(['version' => '2']);
        self::assertStringStartsWith('2-', $raised->getVersion());
    }

    public function testTheClientConfigNamesTheCookiesTheScriptHandles(): void
    {
        $config = (new ConsentManager(['tagId' => 'G-TEST']))->getClientConfig();

        self::assertSame('/application-consent', $config['logUrl']);
        self::assertSame(['analytics' => ['_ga', '_ga_*'], 'external' => []], $config['cookies']);
        self::assertSame(['_cc', '_ga', '_ga_*'], $config['declared']);

        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);
        $module->enableConsentLog = false;

        self::assertNull((new ConsentManager())->getClientConfig()['logUrl']);
    }

    public function testCookieDurationLabels(): void
    {
        self::assertSame('2 years', Cookie::make()->duration('P2Y')->getDurationLabel());
        self::assertSame('1 month', Cookie::make()->duration('P1M')->getDurationLabel());
        self::assertSame('90 days', Cookie::make()->duration('P90D')->getDurationLabel());
        self::assertSame('End of session', Cookie::make()->getDurationLabel());
    }

    public function testDefaultServicesDescribeTheirProvider(): void
    {
        $service = GoogleAnalytics::make();

        self::assertSame('Google Ireland Limited', $service->getProvider());
        self::assertSame('https://policies.google.com/privacy', $service->getPrivacyUrl());
        self::assertNotSame('', $service->getPurpose());
    }

    /**
     * @return list<string>
     */
    private function getCategoryIds(ConsentManager $consent): array
    {
        return array_map(fn (Category $category): string => $category->getId(), $consent->getCategories());
    }

    /**
     * @return list<string>
     */
    private function getServiceNames(Category $category): array
    {
        return array_map(fn (Service $service): string => $service->getName(), $category->getServices());
    }
}
