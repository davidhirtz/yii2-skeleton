<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\SearchController;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\console\ExitCode;

class SearchControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testActionRebuildIndexesEveryRegisteredClass(): void
    {
        $redirect = $this->createRedirect();

        Search::deleteAll();

        $controller = $this->createSearchController();
        self::assertSame(ExitCode::OK, $controller->actionRebuild());

        $output = $controller->flushStdOutBuffer();

        self::assertStringContainsString('Indexing ' . Redirect::class, $output);
        self::assertStringContainsString('Indexing ' . User::class, $output);

        self::assertSame(1, (int)Search::find()->where(['model_class' => Redirect::class])->count());
        self::assertSame(
            (int)User::find()->count(),
            (int)Search::find()->where(['model_class' => User::class])->count()
        );

        $document = Search::findOne(['model_class' => Redirect::class, 'model_id' => $redirect->id]);
        self::assertSame('alte-firma', $document->title);
    }

    public function testActionRebuildFiltersByShortName(): void
    {
        $this->createRedirect();
        Search::deleteAll();

        $controller = $this->createSearchController();
        $controller->actionRebuild('Redirect');

        self::assertStringNotContainsString('Indexing ' . User::class, $controller->flushStdOutBuffer());
        self::assertEmpty(Search::find()->where(['model_class' => User::class])->all());
        self::assertNotEmpty(Search::find()->where(['model_class' => Redirect::class])->all());
    }

    public function testActionClearRemovesEverything(): void
    {
        $this->createRedirect();

        $controller = $this->createSearchController();
        self::assertSame(ExitCode::OK, $controller->actionClear());

        self::assertStringContainsString('Clearing search index ...', $controller->flushStdOutBuffer());
        self::assertEmpty(Search::find()->all());
    }

    public function testActionClearRemovesOneClass(): void
    {
        $this->createRedirect();

        $controller = $this->createSearchController();
        $controller->actionClear(Redirect::class);

        self::assertEmpty(Search::find()->where(['model_class' => Redirect::class])->all());
    }

    public function testTheCommandsRefuseToRunWhileTheSearchIsDisabled(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->enableSearch = false;

        $controller = $this->createSearchController();

        self::assertSame(ExitCode::CONFIG, $controller->actionRebuild());
        self::assertSame(ExitCode::CONFIG, $controller->actionClear());

        self::assertStringContainsString('The search index is disabled', $controller->flushStdOutBuffer());
    }

    private function createRedirect(): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'alte-firma';
        $redirect->url = 'neue-firma';

        self::assertTrue($redirect->insert());

        return $redirect;
    }

    private function createSearchController(): SearchControllerMock
    {
        return new SearchControllerMock('search', Yii::$app);
    }
}

class SearchControllerMock extends SearchController
{
    use StdOutBufferControllerTrait;
}
