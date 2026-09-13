<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Override;
use Yii;
use yii\web\NotFoundHttpException;

class AdminAliasTest extends TestCase
{
    use FunctionalTestTrait;

    #[Override]
    protected function setUp(): void
    {
        $config = require(__DIR__ . '/../../config/test.php');
        $config['params']['adminAlias'] = 'backend';

        $this->config = $config;

        parent::setUp();
    }

    public function testTheAliasReplacesTheDefaultPath(): void
    {
        $this->open('backend/account/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/backend/account/login"]');
        self::assertSelectorExists('a[href="/backend/account/recover"]');
    }

    /**
     * Run directly: a request would be claimed by a bundle's catch-all rule before it reaches the module.
     */
    public function testTheDefaultPathIsRefused(): void
    {
        Yii::$app->getRequest()->setPathInfo('admin/account/login');

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/account/login');
    }

    public function testTheAliasPathIsServed(): void
    {
        Yii::$app->getRequest()->setPathInfo('backend/account/login');

        self::assertIsString(Yii::$app->runAction('admin/account/login'));
    }
}
