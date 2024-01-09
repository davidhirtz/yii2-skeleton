<?php

namespace davidhirtz\yii2\skeleton\tests\unit\web;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\tests\data\controllers\TestController;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;

class ErrorActionTest extends Unit
{
    public function testYiiException(): void
    {
        $message = 'This message will not be shown to the user';
        Yii::$app->getErrorHandler()->exception = new InvalidConfigException($message);

        $result = $this->runErrorAction();

        $this->assertStringContainsString('An internal server error occurred.', $result);
        $this->assertStringNotContainsString($message, $result);
    }

    public function testUserException(): void
    {
        $message = 'User can see this error message';
        Yii::$app->getErrorHandler()->exception = new UserException($message);

        $this->assertStringContainsString($message, $this->runErrorAction());
    }

    public function testForbiddenException(): void
    {
        Yii::$app->getErrorHandler()->exception = new ForbiddenHttpException();
        $this->assertStringContainsString('Permission denied', $this->runErrorAction());
    }

    public function testNotFoundTranslated(): void
    {
        Yii::$app->language = 'de';
        $error = Yii::t('skeleton', 'Error') . ' 404';

        $result = $this->runErrorAction([
            'layout' => '@tests/data/views/layouts/main',
        ]);

        $this->assertStringContainsString(Yii::t('skeleton', 'The requested page was not found'), $result);
        $this->assertEquals(404, Yii::$app->getResponse()->getStatusCode());
        $this->assertStringContainsString("<title>$error</title>", $result);
    }

    public function testAjaxRequest(): void
    {
        Yii::$app->getRequest()->getHeaders()->set('X-Requested-With', 'XMLHttpRequest');
        $this->assertEquals('The requested page was not found', $this->runErrorAction());
    }

    public function testNoExceptionInHandler(): void
    {
        $this->assertStringContainsString('The requested page was not found', $this->runErrorAction());
        $this->assertEquals(404, Yii::$app->getResponse()->getStatusCode());
    }

    public function testInvalidView(): void
    {
        $this->expectException('yii\base\ViewNotFoundException');
        $this->expectExceptionMessage('The view file does not exist: ./resources/views/test/invalid.php');

        $controller = $this->getController([
            'view' => 'invalid',
        ]);

        $controller->runAction('error');
    }

    public function testLayout(): void
    {
        $this->expectException('yii\base\ViewNotFoundException');
        $this->expectExceptionMessage('The view file does not exist: ./resources/views/layouts/non-existing.php');

        $controller = $this->getController([
            'layout' => 'non-existing',
        ]);

        $controller->runAction('error');
    }

    protected function getController(array $config = []): TestController
    {
        return new TestController('test', Yii::$app, [
            'layout' => false,
            'config' => $config,
        ]);
    }

    protected function runErrorAction(array $config = []): string
    {
        return $this->getController($config)->runAction('error');
    }
}
