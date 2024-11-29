<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\controllers\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\controllers\traits\AjaxRouteTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\web\View;

class AjaxRouteTraitTest extends Unit
{
    public function testRegularRequest(): void
    {
        $controller = new AjaxRouteControllerMock('test', Yii::$app);
        self::assertStringStartsWith('<!DOCTYPE html>', $controller->actionIndex());
    }

    public function testAjaxRequest(): void
    {
        Yii::$app->getRequest()->getHeaders()->set('X-Requested-With', 'XMLHttpRequest');

        $controller = new AjaxRouteControllerMock('test', Yii::$app);
        self::assertStringStartsWith('<!DOCTYPE html>', $controller->actionIndex());
    }

    public function testAjaxRouteRequest(): void
    {
        $this->setAjaxRouteMock();

        $controller = new AjaxRouteControllerMock('test', Yii::$app);

        $expected = '<div class="container">Test content</div><script>document.title="Test title"</script>';
        self::assertEquals($expected, $controller->actionIndex());
    }

    public function testAjaxRouteRequestWithAssets(): void
    {
        $this->setAjaxRouteMock();

        Yii::$app->getView()->registerJs('alert("test")', View::POS_END);
        Yii::$app->getView()->registerCss('body{background:#000;}');

        $controller = new AjaxRouteControllerMock('test', Yii::$app);
        $content = $controller->actionIndex();

        self::assertStringContainsString('<style>body{background:#000;}</style>', $content);
        self::assertStringContainsString('<script>alert("test");document.title="Test title"</script>', $content);
    }

    protected function setAjaxRouteMock(): void
    {
        Yii::$app->getRequest()->getHeaders()->set('X-Requested-With', 'XMLHttpRequest');
        $_SERVER['HTTP_X_AJAX_REQUEST'] = 'route';
    }
}

class AjaxRouteControllerMock extends Controller
{
    use AjaxRouteTrait;

    public $layout = '@tests/data/views/layouts/main';
    public bool $spacelessOutput = true;

    public function actionIndex(): string
    {
        return $this->render('@tests/data/views/test', [
            'content' => 'Test content'
        ]);
    }
}
