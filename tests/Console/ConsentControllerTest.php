<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\ConsentController;
use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class ConsentControllerTest extends TestCase
{
    /**
     * `OPTIMIZE TABLE` commits the test's transaction.
     */
    #[Override]
    protected function tearDown(): void
    {
        Consent::deleteAll();
        parent::tearDown();
    }

    public function testActionClearWithoutLifetime(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->createController()->actionClear();
    }

    public function testActionClear(): void
    {
        $this->insertConsent('-2 years');
        $this->insertConsent('-1 hour');

        $controller = $this->createController();
        $controller->sleep = 0;
        $controller->actionClear(60 * 60 * 24 * 731);

        self::assertEquals('No expired consent records found' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertEquals(2, Consent::find()->count());

        $controller->actionClear(60 * 60 * 24);

        self::assertStringContainsString('Deleted 1 expired consent records', $controller->flushStdOutBuffer());
        self::assertEquals(1, Consent::find()->count());
    }

    public function testLifetimeComesFromTheAdminModule(): void
    {
        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);

        $module->consentLifetime = 60;

        $this->insertConsent('-1 day');

        $controller = $this->createController();
        $controller->sleep = 0;
        $controller->actionClear();

        self::assertStringContainsString('Deleted 1 expired consent records', $controller->flushStdOutBuffer());
        self::assertEquals(0, Consent::find()->count());
    }

    private function insertConsent(string $modify): void
    {
        Yii::$app->getDb()->createCommand()
            ->insert(Consent::tableName(), [
                'uuid' => '0b0e8a52-7c4f-4d1a-9b8e-3f6c2a1d5e7f',
                'version' => 'v1',
                'categories' => ['required'],
                'created_at' => gmdate('Y-m-d H:i:s', strtotime($modify) ?: null),
            ])
            ->execute();
    }

    private function createController(): ConsentControllerMock
    {
        return new ConsentControllerMock('consent', Yii::$app);
    }
}

class ConsentControllerMock extends ConsentController
{
    use StdOutBufferControllerTrait;
}
