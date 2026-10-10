<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Controllers\ConsentController;
use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Override;
use Yii;

class ConsentTest extends TestCase
{
    use FunctionalTestTrait;

    private const string UUID = '0b0e8a52-7c4f-4d1a-9b8e-3f6c2a1d5e7f';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getCache()->flush();
    }

    public function testDecisionIsLogged(): void
    {
        $this->postConsent(self::UUID, 'v3', 'required,analytics,analytics');

        self::assertResponseStatusCodeSame(204);

        $consent = Consent::findOne(['uuid' => self::UUID]);

        self::assertNotNull($consent);
        self::assertSame('v3', $consent->version);
        self::assertSame(['required', 'analytics'], $consent->categories);
    }

    public function testWithdrawalWithoutCategoriesIsLogged(): void
    {
        $this->postConsent(self::UUID, 'v3', '');

        self::assertResponseStatusCodeSame(204);
        self::assertSame([], Consent::findOne(['uuid' => self::UUID])?->categories);
    }

    public function testInvalidDecisionIsRefused(): void
    {
        $this->postConsent('not-a-uuid', 'v3', 'required');
        self::assertResponseStatusCodeSame(400);

        $this->postConsent(self::UUID, 'v3', 'required,<script>');
        self::assertResponseStatusCodeSame(400);

        $this->postConsent(self::UUID, 'v3', implode(',', range(1, Consent::CATEGORIES_MAX_COUNT + 1)));
        self::assertResponseStatusCodeSame(400);

        self::assertSame(0, (int)Consent::find()->count());
    }

    public function testGetIsNotAllowed(): void
    {
        $this->open('application-consent');
        self::assertResponseStatusCodeSame(405);
    }

    public function testDisabledLogIsNotFound(): void
    {
        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);

        $module->enableConsentLog = false;

        $this->postConsent(self::UUID, 'v3', 'required');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(0, (int)Consent::find()->count());
    }

    public function testRequestsPerAddressAreLimited(): void
    {
        Yii::$app->controllerMap['consent'] = [
            'class' => ConsentController::class,
            'limit' => 1,
        ];

        $this->postConsent(self::UUID, 'v3', 'required');
        self::assertResponseStatusCodeSame(204);

        $this->postConsent(self::UUID, 'v3', 'required,analytics');
        self::assertResponseStatusCodeSame(429);

        self::assertSame(1, (int)Consent::find()->count());
    }

    private function postConsent(string $uuid, string $version, string $categories): void
    {
        self::$client = new Browser([
            'HTTP_HOST' => 'www.test.localhost',
            'HTTPS' => 'on',
        ]);

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/application-consent', [
            'id' => $uuid,
            'version' => $version,
            'categories' => $categories,
        ]);
    }
}
