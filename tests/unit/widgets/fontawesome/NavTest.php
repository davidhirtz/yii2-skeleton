<?php

namespace davidhirtz\yii2\skeleton\tests\unit\widgets\fontawesome;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use Yii;

/**
 * @property UnitTester $tester
 */
class NavTest extends Unit
{
    protected string $assetBasePath = '@runtime/assets';

    protected function _before(): void
    {
        FileHelper::createDirectory($this->assetBasePath);
        Yii::$app->getAssetManager()->basePath = Yii::getAlias($this->assetBasePath);

        Yii::$app->controllerMap['site'] = TestSiteController::class;
        $this->tester->amOnRoute('site/index');

        parent::_before();
    }

    protected function _after(): void
    {
        FileHelper::removeDirectory($this->assetBasePath);
        parent::_after();
    }

    public function testHideOneItem()
    {
        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                ],
            ],
        ]);

        self::assertEmpty($content);
    }

    public function testItemVisibility()
    {
        $content = Nav::widget([
            'hideOneItem' => false,
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'visible' => false
                ],
            ],
        ]);

        self::assertEmpty($content);
    }

    public function testItemRoles()
    {
        Yii::$app->getUser()->disableRbacForGuests = false;

        $content = Nav::widget([
            'hideOneItem' => false,
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'roles' => ['*']
                ],
                [
                    'label' => 'Admin',
                    'url' => ['site/index'],
                    'roles' => [User::AUTH_ROLE_ADMIN]
                ],
            ],
        ]);

        self::assertStringContainsString('><a class="nav-link active" href="/site/index">Home</a></li>', $content);
    }

    public function testLinkOptions()
    {
        $content = Nav::widget([
            'hideOneItem' => false,
            'linkOptions' => [
                'target' => '_blank',
            ],
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],

                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/index" target="_blank">Home</a>', $content);
    }

    public function testBadgeAndIconOptions()
    {
        $content = Nav::widget([
            'hideOneItem' => false,
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'badge' => 'New',
                    'icon' => 'fas fa-home',
                    'badgeOptions' => [
                        'class' => 'test',
                    ],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/index"><i class="fa-fw fas fa-fas fa-home"></i> <span>Home</span> <span class="test">New</span></a>', $content);
    }

    public function testDropdownItemsCallback()
    {
        $content = Nav::widget([
            'hideOneItem' => false,
            'items' => [
                [
                    'label' => 'Dropdown',
                    'items' => fn () => [
                        [
                            'label' => 'Option 1',
                            'url' => ['site/test'],
                        ],
                        [
                            'label' => 'Option 2',
                            'url' => ['site/index'],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="dropdown-item" href="/site/test">Option 1</a>', $content);
    }

    public function testActiveItemFromUrl()
    {
        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                ],
                [
                    'label' => 'Test',
                    'url' => ['site/test'],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/index">Home</a>', $content);
    }

    public function testActiveItemWithRoutes()
    {
        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/test'],
                ],
                [
                    'label' => 'Test',
                    'url' => ['site/test'],
                    'active' => [
                        'site/index',
                    ],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/test">Test</a>', $content);
    }

    public function testActiveItemWithSkippedRoute()
    {
        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'active' => ['!site/index'],
                ],
                [
                    'label' => 'Test',
                    'url' => ['site/index'],
                ],
            ],
        ]);

        codecept_debug($content);
        self::assertStringContainsString('<a class="nav-link active" href="/site/index">Test</a>', $content);
    }

    public function testActiveItemWithActiveCallback()
    {
        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'active' => fn () => ['site/no-match'],
                ],
                [
                    'label' => 'Test',
                    'url' => ['site/index'],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/index">Test</a>', $content);
    }

    public function testActiveItemWithRequestQueryParameters()
    {
        Yii::$app->getRequest()->setQueryParams(['id' => 1]);

        $content = Nav::widget([
            'items' => [
                [
                    'label' => 'Home',
                    'url' => ['site/index'],
                    'active' => [
                        ['site/index', 'id' => 2],
                    ],
                ],
                [
                    'label' => 'Test',
                    'url' => ['site/index'],
                    'active' => [
                        'site/index' => ['id' => 1],
                    ],
                ],
            ],
        ]);

        self::assertStringContainsString('<a class="nav-link active" href="/site/index">Test</a>', $content);
    }
}

class TestSiteController extends Controller
{
    public function actionIndex(): string
    {
        return '';
    }
}
