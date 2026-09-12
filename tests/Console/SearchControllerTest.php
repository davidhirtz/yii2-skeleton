<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\SearchController;
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
        Search::deleteAll();

        $controller = $this->createSearchController();
        self::assertSame(ExitCode::OK, $controller->actionRebuild());

        self::assertStringContainsString('Indexing ' . User::class, $controller->flushStdOutBuffer());

        self::assertSame(
            (int)User::find()->count(),
            (int)Search::find()->where(['model_class' => User::class])->count()
        );

        $user = $this->getUserFromFixture('admin');
        $document = Search::findOne(['model_class' => User::class, 'model_id' => $user->id]);

        self::assertSame($user->name, $document->title);
        self::assertStringContainsString($user->email, (string)$document->content);
    }

    public function testActionRebuildFiltersByShortName(): void
    {
        Search::deleteAll();

        $controller = $this->createSearchController();
        $controller->actionRebuild('User');

        self::assertStringContainsString('Indexing ' . User::class, $controller->flushStdOutBuffer());
        self::assertNotEmpty(Search::find()->where(['model_class' => User::class])->all());
    }

    public function testAnUnknownNameIndexesNothing(): void
    {
        Search::deleteAll();

        $controller = $this->createSearchController();
        $controller->actionRebuild('Nope');

        self::assertSame('', $controller->flushStdOutBuffer());
        self::assertEmpty(Search::find()->all());
    }

    public function testActionClearRemovesEverything(): void
    {
        $this->createSearchController()->actionRebuild();
        self::assertNotEmpty(Search::find()->all());

        $controller = $this->createSearchController();
        self::assertSame(ExitCode::OK, $controller->actionClear());

        self::assertStringContainsString('Clearing search index ...', $controller->flushStdOutBuffer());
        self::assertEmpty(Search::find()->all());
    }

    public function testActionClearRemovesOneClass(): void
    {
        $this->createSearchController()->actionRebuild();
        self::assertNotEmpty(Search::find()->where(['model_class' => User::class])->all());

        $controller = $this->createSearchController();
        $controller->actionClear(User::class);

        self::assertEmpty(Search::find()->where(['model_class' => User::class])->all());
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

    private function createSearchController(): SearchControllerMock
    {
        return new SearchControllerMock('search', Yii::$app);
    }
}

class SearchControllerMock extends SearchController
{
    use StdOutBufferControllerTrait;
}
