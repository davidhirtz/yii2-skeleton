<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\RecentlyEditedPanel;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class RecentlyEditedPanelTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);
        $module->dashboardTrailModels = [Redirect::class];
    }

    public function testParentRowsCountForTheRecordAndEachRecordIsListedOnceNewestFirst(): void
    {
        $first = $this->createRedirect('first');
        $second = $this->createRedirect('second');

        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->insertTrail($first, Trail::TYPE_UPDATE);
        $this->insertTrail($second, Trail::TYPE_UPDATE);
        $this->insertTrail($first, Trail::TYPE_CHILD_UPDATE);

        $html = RecentlyEditedPanel::make()->render();

        self::assertSame(1, substr_count($html, $this->getHref($first)));
        self::assertSame(1, substr_count($html, $this->getHref($second)));
        self::assertLessThan(strpos($html, $this->getHref($second)), strpos($html, $this->getHref($first)));
    }

    public function testAnotherUsersEditsAndDeletedRecordsAreLeftOut(): void
    {
        $others = $this->createRedirect('others');
        $deletedRow = $this->createRedirect('deleted-row');
        $gone = $this->createRedirect('gone');
        $mine = $this->createRedirect('mine');

        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($owner);

        $this->insertTrail($others, Trail::TYPE_UPDATE, $this->getUserFromFixture('admin')->id);
        $this->insertTrail($deletedRow, Trail::TYPE_UPDATE);
        $this->insertTrail($deletedRow, Trail::TYPE_DELETE);
        $this->insertTrail($gone, Trail::TYPE_UPDATE);
        $this->insertTrail($mine, Trail::TYPE_UPDATE);

        $goneHref = $this->getHref($gone);
        Redirect::deleteAll(['id' => $gone->id]);

        $html = RecentlyEditedPanel::make()->render();

        self::assertStringContainsString($this->getHref($mine), $html);
        self::assertStringNotContainsString($this->getHref($others), $html);
        self::assertStringNotContainsString($this->getHref($deletedRow), $html);
        self::assertStringNotContainsString($goneHref, $html);
    }

    public function testTheTitleLinksToTheUsersTrailAndAtMostFiveRecordsAreListed(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($owner);

        $redirects = [];

        for ($i = 1; $i <= 6; $i++) {
            $redirects[$i] = $this->createRedirect("limit-$i");
            $this->insertTrail($redirects[$i], Trail::TYPE_UPDATE);
        }

        $html = RecentlyEditedPanel::make()->render();

        self::assertStringContainsString('href="' . Url::to(['/admin/user-trail/index', 'id' => $owner->id]) . '"', $html);
        self::assertStringContainsString($this->getHref($redirects[6]), $html);
        self::assertStringNotContainsString($this->getHref($redirects[1]), $html);
    }

    public function testNothingRendersWithoutListedClassesOrRows(): void
    {
        $redirect = $this->createRedirect('unlisted');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        self::assertSame('', RecentlyEditedPanel::make()->render());

        $this->insertTrail($redirect, Trail::TYPE_UPDATE);

        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);
        $module->dashboardTrailModels = [];

        self::assertSame('', RecentlyEditedPanel::make()->render());
    }

    public function testARecordTheUserCannotOpenIsNotALink(): void
    {
        $redirect = $this->createRedirect('no-permission');

        $user = $this->getUserFromFixture('admin');
        $this->getWebUser()->setIdentity($user);
        self::assertFalse($this->getWebUser()->can(Redirect::AUTH_REDIRECT));

        $this->insertTrail($redirect, Trail::TYPE_UPDATE);

        $html = RecentlyEditedPanel::make()->render();

        self::assertStringContainsString($redirect->getAdminName(), $html);
        self::assertStringNotContainsString($this->getHref($redirect), $html);
        self::assertStringNotContainsString('user-trail', $html);
    }

    private function createRedirect(string $name): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = "recently-edited-$name-å-東京";
        $redirect->url = 'https://www.example.com/';

        self::assertTrue($redirect->insert(), print_r($redirect->getErrors(), true));

        // Each test writes the rows it reads.
        Trail::deleteAll(['model_class' => Redirect::class, 'model_id' => $redirect->id]);

        return $redirect;
    }

    /**
     * `Trail::beforeSave()` writes the logged-in user, so another user's row is moved over afterwards.
     */
    private function insertTrail(Redirect $redirect, int $type, ?int $userId = null): void
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model_class = Redirect::class;
        $trail->model_id = $redirect->id;

        self::assertTrue($trail->insert());

        if ($userId !== null) {
            $trail->updateAttributes(['user_id' => $userId]);
        }
    }

    private function getHref(Redirect $redirect): string
    {
        $route = $redirect->getAdminRoute();
        self::assertNotFalse($route);

        return 'href="' . Url::to($route) . '"';
    }
}
