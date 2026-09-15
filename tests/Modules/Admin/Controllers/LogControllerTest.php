<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Modules\Admin\Data\LogFileArrayDataProvider;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class LogControllerTest extends TestCase
{
    use UserFixtureTrait;

    private string $logPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = Yii::getAlias('@runtime/test-logs') . '/';
        FileHelper::createDirectory($this->logPath);

        Yii::$container->set(LogDataProvider::class, ['basePath' => $this->logPath]);
        Yii::$container->set(LogFileArrayDataProvider::class, ['basePath' => $this->logPath]);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->logPath);
        parent::tearDown();
    }

    public function testIndexListsTheLogFiles(): void
    {
        $this->login();

        $this->createLogFile('app.log');
        $this->createLogFile('cron.log');

        $html = Yii::$app->runAction('admin/log/index');

        self::assertIsString($html);
        self::assertStringContainsString('app.log', $html);
        self::assertStringContainsString('cron.log', $html);
    }

    public function testIndexWithASingleFileGoesStraightToIt(): void
    {
        $this->login();
        $this->createLogFile('app.log');

        $response = Yii::$app->runAction('admin/log/index');

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('log=app.log', $response->getHeaders()->get('location'));
    }

    public function testIndexIsForbiddenForANonAdmin(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/log/index');
    }

    public function testViewSplitsTheFileIntoEntries(): void
    {
        $this->login();
        $this->createLogFile('app.log');

        $html = Yii::$app->runAction('admin/log/view', ['log' => 'app.log']);

        self::assertIsString($html);
        self::assertStringContainsString('Something went wrong', $html);
        self::assertStringContainsString('And something else did too', $html);
    }

    /**
     * An `info` entry sharing the date of the entry above it holds that request's parameters, so it is appended to
     * it rather than listed as an entry of its own.
     */
    public function testViewAppendsAnInfoEntryToTheOneAboveIt(): void
    {
        $this->login();

        $provider = Yii::$container->get(LogDataProvider::class, config: ['file' => $this->createLogFile('app.log')]);

        self::assertCount(2, $provider->allModels);

        // the newest entry comes first
        self::assertSame('And something else did too', $provider->allModels[0]->message);
        self::assertSame('Something went wrong', $provider->allModels[1]->message);
        self::assertStringContainsString('$_GET = []', $provider->allModels[1]->content);
    }

    public function testViewOfAnUnknownFileIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/log/view', ['log' => 'nope.log']);
    }

    public function testViewCannotEscapeTheLogDirectory(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/log/view', ['log' => '../../config/db.php']);
    }

    public function testViewSendsTheRawFile(): void
    {
        $this->login();
        $this->createLogFile('app.log');

        $response = Yii::$app->runAction('admin/log/view', ['log' => 'app.log', 'raw' => true]);

        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('text/plain', $response->getHeaders()->get('content-type'));
    }

    public function testDeleteRemovesTheFile(): void
    {
        $this->login();
        $this->createLogFile('app.log');

        $response = $this->post('admin/log/delete', ['log' => 'app.log']);

        self::assertInstanceOf(Response::class, $response);
        self::assertFileDoesNotExist($this->logPath . 'app.log');
    }

    public function testDeleteOfAnUnknownFileIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->post('admin/log/delete', ['log' => 'nope.log']);
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();
        $this->createLogFile('app.log');

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/log/delete', ['log' => 'app.log']);
    }

    private function createLogFile(string $name): string
    {
        $content = <<<'LOG'
            2026-09-13 10:00:00 [127.0.0.1][1][-][error][yii\base\ErrorException] Something went wrong
            Stack trace:
            #0 /app/index.php(1)
            2026-09-13 10:00:00 [127.0.0.1][1][-][info][application] $_GET = []
            2026-09-13 11:00:00 [127.0.0.1][1][-][warning][application] And something else did too
            LOG;

        file_put_contents($this->logPath . $name, $content . "\n");

        return $name;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
