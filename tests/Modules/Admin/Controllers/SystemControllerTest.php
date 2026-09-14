<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\caching\ArrayCache;
use yii\caching\FileCache;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SystemControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testIndexReportsTheInstallation(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/system/index');

        self::assertIsString($html);

        // the installation as a maintainer reads it off a client's site
        self::assertStringContainsString(Yii::$app->name, $html);
        self::assertStringContainsString('>yii2-skeleton</span>', $html);
        self::assertStringContainsString(PHP_VERSION, $html);
        self::assertStringContainsString(Yii::getVersion(), $html);
        self::assertStringContainsString('system/php-info', $html);

        // the server facts and the maintenance actions live on their own tabs
        self::assertStringNotContainsString('<div class="form-label">Trusted hosts</div>', $html);
        self::assertStringNotContainsString('system/flush', $html);
    }

    public function testServerReportsTheHostAndItsConfiguration(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/system/server');

        self::assertIsString($html);
        self::assertStringContainsString('<div class="form-label">Trusted hosts</div>', $html);
        self::assertStringContainsString('<div class="form-label">Mailer</div>', $html);
        self::assertStringContainsString('<div class="form-label">Time zone</div>', $html);
    }

    public function testServerIsForbiddenForANonAdmin(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/system/server');
    }

    public function testMaintenanceCarriesEveryAction(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/system/maintenance');

        self::assertIsString($html);

        // one row per configured cache component, each with its flush button
        self::assertStringContainsString(ArrayCache::class, $html);
        self::assertStringContainsString('system/flush', $html);
        self::assertStringContainsString('system/publish', $html);
        self::assertStringContainsString('system/schema', $html);
        self::assertStringContainsString('system/session-gc', $html);
    }

    public function testEveryTabIsLinkedFromEachOther(): void
    {
        $this->login();

        foreach (['index', 'server', 'maintenance'] as $action) {
            $html = (string)Yii::$app->runAction("admin/system/$action");

            self::assertStringContainsString('class="tabs nav"', $html);
            self::assertStringContainsString('href="/admin/system/index"', $html);
            self::assertStringContainsString('href="/admin/system/server"', $html);
            self::assertStringContainsString('href="/admin/system/maintenance"', $html);
        }
    }

    public function testMaintenanceIsForbiddenForANonAdmin(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/system/maintenance');
    }

    public function testIndexWarnsAboutAPendingMigration(): void
    {
        $this->login();

        self::assertStringNotContainsString(
            'not been applied',
            (string)Yii::$app->runAction('admin/system/index'),
        );

        Yii::$app->getDb()->createCommand()
            ->delete('{{%migration}}', [
                'version' => (string)Yii::$app->getDb()
                    ->createCommand('SELECT version FROM {{%migration}} LIMIT 1')
                    ->queryScalar(),
            ])
            ->execute();

        self::assertStringContainsString(
            'not been applied',
            (string)Yii::$app->runAction('admin/system/index'),
        );
    }

    public function testPhpInfoIsRenderedWithoutTheAdminLayout(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/system/php-info');

        self::assertIsString($html);
        self::assertStringContainsString(PHP_VERSION, $html);
        self::assertStringNotContainsString('<div class="wrap"', $html);
    }

    public function testPhpInfoIsForbiddenForANonAdmin(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/system/php-info');
    }

    public function testIndexIsForbiddenForANonAdmin(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/system/index');
    }

    public function testFlushEmptiesTheCache(): void
    {
        $this->login();
        Yii::$app->getCache()->set('key', 'value');

        $response = $this->post('admin/system/flush', ['cache' => 'cache']);

        self::assertInstanceOf(Response::class, $response);
        self::assertFalse(Yii::$app->getCache()->get('key'));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testFlushOfAComponentThatIsNotACacheIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/system/flush', ['cache' => 'db']);
    }

    public function testFlushRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/system/flush', ['cache' => 'cache']);
    }

    public function testPublishRemovesThePublishedAssets(): void
    {
        $this->login();

        $basePath = Yii::$app->getAssetManager()->basePath;
        $directory = "$basePath/abc123";

        self::assertTrue(mkdir($directory, 0o775, true));

        $this->post('admin/system/publish');

        self::assertDirectoryDoesNotExist($directory);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testSessionGcRunsAgainstTheSessionComponent(): void
    {
        $this->login();

        $response = $this->post('admin/system/session-gc');

        self::assertInstanceOf(Response::class, $response);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testSchemaRefreshesTheConnection(): void
    {
        $this->login();

        $response = $this->post('admin/system/schema', ['db' => 'db']);

        self::assertInstanceOf(Response::class, $response);
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testSchemaOfAComponentThatIsNotAConnectionIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/system/schema', ['db' => 'cache']);
    }

    public function testTheCacheListIsNotCarriedIntoTheNextApplication(): void
    {
        // `TestCase` swaps the file cache for an `ArrayCache`, so a list kept across applications would be stale
        self::assertSame(ArrayCache::class, CacheComponents::getAll()['cache']);
        self::assertNotSame(FileCache::class, CacheComponents::getAll()['cache']);
    }

    private function post(string $route, array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
