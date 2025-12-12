<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Console\Controllers\ParamsController;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class ParamsControllerTest extends TestCase
{
    private string $configPath = '@runtime/config';

    protected function setUp(): void
    {
        parent::setUp();
        FileHelper::createDirectory($this->configPath);
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->configPath);
        parent::tearDown();
    }

    public function testActionIndex(): void
    {
        $controller = $this->createParamsController();
        $controller->actionIndex();

        self::assertStringContainsString("- *cookieValidationKey*  'test'" . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testActionIndexWithoutCookieValidationKey(): void
    {
        Yii::$app->params = [];

        $controller = $this->createParamsController();
        $controller->actionIndex();

        self::assertEquals('Generate cookie validation key? (yes|no) [yes]:', $controller->flushStdOutBuffer());
    }

    public function testActionCookie(): void
    {
        $controller = $this->createParamsController();
        $controller->interactive = false;

        $controller->actionCookie();

        self::assertEquals('Cookie validation key generated.' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertNotEquals('test', Yii::$app->params['cookieValidationKey']);

        $filename = Yii::getAlias("$this->configPath/params.php");
        self::assertFileExists($filename);

        $contents = file_get_contents($filename);
        self::assertStringContainsString(Yii::$app->params['cookieValidationKey'], $contents);
    }

    public function testActionCreate(): void
    {
        $controller = $this->createParamsController();
        $controller->interactive = false;

        $controller->actionCreate('test', 'test');

        self::assertEquals('Application parameter added.' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertEquals('test', Yii::$app->params['test']);

        $filename = Yii::getAlias("$this->configPath/params.php");
        self::assertFileExists($filename);

        $contents = file_get_contents($filename);
        self::assertStringContainsString('test', $contents);
    }

    public function testActionUpdate(): void
    {
        $controller = $this->createParamsController();
        $controller->interactive = false;

        $controller->actionUpdate('test', 'true');
        self::assertTrue(Yii::$app->params['test']);

        $controller->actionUpdate('test', '1');
        self::assertTrue(Yii::$app->params['test']);

        $controller->actionCreate('test', 'false');
        self::assertFalse(Yii::$app->params['test']);

        $controller->actionUpdate('test', '0');
        self::assertFalse(Yii::$app->params['test']);

        $controller->actionUpdate('test', 'null');
        self::assertNull(Yii::$app->params['test']);

        $controller->actionUpdate('test', '12345');
        self::assertEquals('12345', Yii::$app->params['test']);

        $controller->actionUpdate('test', '"quoted string"');
        self::assertEquals('quoted string', Yii::$app->params['test']);
    }

    public function testActionDelete(): void
    {
        $controller = $this->createParamsController();
        $controller->interactive = false;

        $controller->actionCreate('test', 'test');
        self::assertEquals('test', Yii::$app->params['test']);

        $controller->actionDelete('test');
        self::assertArrayNotHasKey('test', Yii::$app->params);
    }

    protected function createParamsController(): TestParamsController
    {
        return new TestParamsController('params', Yii::$app, [
            'config' => "$this->configPath/params.php",
        ]);
    }
}

class TestParamsController extends ParamsController
{
    use StdOutBufferControllerTrait;
}
