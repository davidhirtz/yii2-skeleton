<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Routing;

use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class ConfiguredRoutesTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        $this->config = [
            ...require(__DIR__ . '/../../config/test.php'),
            'routes' => [
                Route::to('offers/{slug}', 'shop/offer/view')
                    ->where('slug', Route::PATTERN_SEGMENT)
                    ->name('shop.offer.view'),
                Route::to('legacy', 'shop/site/legacy')
                    ->first()
                    ->name('shop.legacy'),
            ],
        ];

        parent::setUp();
    }

    public function testConfiguredRoutesAreRegistered(): void
    {
        $generator = Yii::$app->getUrlGenerator();

        self::assertTrue($generator->hasRoute('shop.offer.view'));
        self::assertSame('/offers/summer', $generator->generate('shop.offer.view', ['slug' => 'summer']));
    }

    public function testConfiguredRoutePositionIsHonoured(): void
    {
        $patterns = array_map(
            static fn ($rule): string => (string)$rule->name,
            Yii::$app->getUrlManager()->rules
        );

        $legacy = array_search('legacy', $patterns, true);
        $health = array_search('application-health', $patterns, true);

        self::assertNotFalse($legacy);
        self::assertNotFalse($health);
        self::assertLessThan($health, $legacy, 'A ->first() config route must sort before the default routes.');
    }

    public function testConfiguredRouteCoexistsWithArrayRules(): void
    {
        self::assertTrue(Yii::$app->getUrlManager()->hasRoute('shop.offer.view'));
        self::assertTrue(Yii::$app->getUrlManager()->hasRoute('health'));
        self::assertSame('/application-health', Yii::$app->getUrlManager()->generate('health'));
    }
}
