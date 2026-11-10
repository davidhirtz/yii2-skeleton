<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ActivityPanel;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class ActivityPanelTest extends TestCase
{
    use UserFixtureTrait;

    public function testParentRowsAreLeftOut(): void
    {
        $child = $this->createRedirect('child');
        $original = $this->createRedirect('original');

        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->insertTrail($original, Trail::TYPE_UPDATE);
        $this->insertTrail($child, Trail::TYPE_CHILD_UPDATE);

        $html = ActivityPanel::make()->render();

        self::assertStringContainsString($this->getHref($original), $html);
        self::assertStringNotContainsString($this->getHref($child), $html);
        self::assertStringContainsString(Url::to(['/admin/trail/index']), $html);
    }

    public function testRepeatedSavesOfOneRecordReadAsOneLine(): void
    {
        $redirect = $this->createRedirect('repeated');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->insertTrail($redirect, Trail::TYPE_UPDATE);
        $this->insertTrail($redirect, Trail::TYPE_UPDATE);
        $this->insertTrail($redirect, Trail::TYPE_UPDATE);

        $html = ActivityPanel::make()->render();

        self::assertSame(1, substr_count($html, $this->getHref($redirect)));
        self::assertStringContainsString(Yii::t('skeleton', 'DASHBOARD_ACTIVITY_COUNT_TEXT', ['count' => 3]), $html);
    }

    public function testACreateFoldsIntoItsSavesButADeletionStandsAlone(): void
    {
        $redirect = $this->createRedirect('lifecycle');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->insertTrail($redirect, Trail::TYPE_CREATE);
        $this->insertTrail($redirect, Trail::TYPE_UPDATE);
        $this->insertTrail($redirect, Trail::TYPE_DELETE);

        $html = ActivityPanel::make()->render();

        self::assertStringContainsString(Yii::t('skeleton', 'DASHBOARD_ACTIVITY_COUNT_TEXT', ['count' => 2]), $html);
        self::assertStringContainsString(Yii::t('skeleton', 'TRAIL_DELETE_NAME'), $html);
    }

    public function testSavesFarApartAreSeparateLines(): void
    {
        $redirect = $this->createRedirect('apart');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->insertTrail($redirect, Trail::TYPE_UPDATE, new DateTime('-1 hour'));
        $this->insertTrail($redirect, Trail::TYPE_UPDATE);

        self::assertSame(2, substr_count(ActivityPanel::make()->render(), $this->getHref($redirect)));
    }

    public function testAUserWithoutTheTrailPermissionSeesNoPanel(): void
    {
        $redirect = $this->createRedirect('hidden');

        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        self::assertFalse($this->getWebUser()->can(Trail::AUTH_TRAIL_INDEX));

        $this->insertTrail($redirect, Trail::TYPE_UPDATE);

        self::assertSame('', ActivityPanel::make()->render());
    }

    private function createRedirect(string $name): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = "activity-$name-ą-😀";
        $redirect->url = 'https://www.example.com/';

        self::assertTrue($redirect->insert(), print_r($redirect->getErrors(), true));

        // Each test writes the rows it reads.
        Trail::deleteAll(['model_class' => Redirect::class, 'model_id' => $redirect->id]);

        return $redirect;
    }

    /**
     * `Trail::beforeSave()` stamps the time, so an earlier one is written afterwards.
     */
    private function insertTrail(Redirect $redirect, int $type, ?DateTime $createdAt = null): void
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model_class = Redirect::class;
        $trail->model_id = $redirect->id;

        self::assertTrue($trail->insert());

        if ($createdAt !== null) {
            $trail->updateAttributes(['created_at' => $createdAt]);
        }
    }

    private function getHref(Redirect $redirect): string
    {
        $route = $redirect->getAdminRoute();
        self::assertNotFalse($route);

        return 'href="' . Url::to($route) . '"';
    }
}
