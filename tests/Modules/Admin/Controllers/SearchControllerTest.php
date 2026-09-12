<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\base\InlineAction;
use yii\web\NotFoundHttpException;

/**
 * The documents are committed, since InnoDB fulltext does not see uncommitted rows, and removed by hand.
 */
class SearchControllerTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getDb()->getTransaction()?->commit();
    }

    #[Override]
    protected function tearDown(): void
    {
        Search::deleteAll();
        parent::tearDown();
    }

    public function testSuggestRendersTheHits(): void
    {
        $user = $this->loginUser(User::AUTH_USER_UPDATE);
        $this->index($user);

        $html = $this->createSearchController()->actionSuggest($user->name);

        self::assertStringContainsString('id="' . SearchController::LIST_ID . '"', $html);
        self::assertStringContainsString('<mark>' . $user->name . '</mark>', $html);
        self::assertStringContainsString('/admin/user/update?id=' . $user->id, $html);
    }

    public function testSuggestHidesWhatTheUserMayNotSee(): void
    {
        $user = $this->loginUser();
        $this->index($user);

        $html = $this->createSearchController()->actionSuggest($user->name);

        self::assertStringContainsString('search-result-empty', $html);
        self::assertStringNotContainsString('/admin/user/update', $html);
    }

    public function testSuggestHidesTheOwnerFromOtherUsers(): void
    {
        $this->loginUser(User::AUTH_USER_UPDATE);

        $owner = $this->getUserFromFixture('owner');
        $this->index($owner);

        $html = $this->createSearchController()->actionSuggest($owner->name);

        self::assertStringContainsString('search-result-empty', $html);
        self::assertStringNotContainsString('/admin/user/update?id=' . $owner->id, $html);
    }

    public function testSuggestWithoutAQueryRendersNothing(): void
    {
        $this->loginUser(User::AUTH_USER_UPDATE);
        self::assertSame('', $this->createSearchController()->actionSuggest('  '));
    }

    public function testIndexRendersTheResultsPage(): void
    {
        $user = $this->loginUser(User::AUTH_USER_UPDATE);
        $this->index($user);

        $html = $this->createSearchController()->actionIndex($user->name);

        self::assertIsString($html);
        self::assertStringContainsString('<mark>' . $user->name . '</mark>', $html);

        // The navbar is outside `#wrap`, so the input has to override the inherited htmx attributes itself.
        self::assertStringContainsString('class="navbar-search"', $html);
        self::assertStringContainsString('hx-select-oob="unset"', $html);
        self::assertStringContainsString('hx-select="#' . SearchController::LIST_ID . '"', $html);
        self::assertStringContainsString('hx-swap="innerHTML"', $html);
    }

    public function testTheActionsAreGoneWhileTheSearchIsDisabled(): void
    {
        $this->loginUser(User::AUTH_USER_UPDATE);

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->enableSearch = false;

        $controller = $this->createSearchController();

        $this->expectException(NotFoundHttpException::class);
        $controller->beforeAction(new InlineAction('suggest', $controller, 'actionSuggest'));
    }

    private function index(SearchableInterface $model): void
    {
        Yii::$app->get('search')->index($model);
    }

    private function loginUser(?string $permission = null): User
    {
        $user = $this->getUserFromFixture('admin');

        if ($permission !== null) {
            $this->assignPermission($user->id, $permission);
        }

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }

    private function createSearchController(): SearchController
    {
        $controller = Yii::$app->getModule('admin')->createControllerByID('search');
        self::assertInstanceOf(SearchController::class, $controller);

        return $controller;
    }
}
