<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Modules\Admin\Module;
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
        self::assertStringContainsString('href="' . Url::to(['/admin/trail/index']) . '"', $html);
    }

    public function testTheUsersOwnChangesAreLeftOutButSystemChangesAreNot(): void
    {
        $own = $this->createRedirect('own');
        $system = $this->createRedirect('system');
        $other = $this->createRedirect('other');

        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($owner);

        $this->insertTrail($own, Trail::TYPE_UPDATE, userId: $owner->id);
        $this->insertTrail($system, Trail::TYPE_UPDATE)->updateAttributes(['user_id' => null]);
        $this->insertTrail($other, Trail::TYPE_UPDATE);

        $html = ActivityPanel::make()->render();

        self::assertStringNotContainsString($this->getHref($own), $html);
        self::assertStringContainsString($this->getHref($system), $html);
        self::assertStringContainsString($this->getHref($other), $html);
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

    public function testADeletedRecordKeepsItsName(): void
    {
        $redirect = $this->createRedirect('deleted');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $name = $redirect->getAdminName();
        self::assertSame(1, $redirect->delete());

        $trail = Trail::find()
            ->where(['model_class' => Redirect::class, 'model_id' => $redirect->id, 'type' => Trail::TYPE_DELETE])
            ->one();

        self::assertInstanceOf(Trail::class, $trail);
        self::assertSame($name, $trail->getModelName());

        $trail->updateAttributes(['user_id' => $this->getUserFromFixture('admin')->id]);
        self::assertStringContainsString('>' . $name . '</span>', ActivityPanel::make()->render());
    }

    public function testAChangeInsideAListedRecordIsShownOnIt(): void
    {
        $entry = $this->createRedirect('listed-parent');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);
        $module->dashboardTrailModels = [Redirect::class];

        $child = Trail::create();
        $child->type = Trail::TYPE_DELETE;
        $child->model_class = UserToken::class;
        $child->model_id = 999_999;
        $child->data = ['name' => 'Bild Å 東京 😀'];
        self::assertTrue($child->insert());

        $copy = Trail::create();
        $copy->type = Trail::TYPE_CHILD_DELETE;
        $copy->model_class = Redirect::class;
        $copy->model_id = $entry->id;
        $copy->data = ['model_class' => UserToken::class, 'model_id' => 999_999, 'trail_id' => $child->id];
        self::assertTrue($copy->insert());

        Trail::updateAll(['user_id' => $this->getUserFromFixture('admin')->id], ['id' => [$child->id, $copy->id]]);

        $html = ActivityPanel::make()->render();

        self::assertStringContainsString($this->getHref($entry), $html);
        self::assertStringContainsString(Yii::t('skeleton', 'TRAIL_CHILD_DELETE_TEXT', ['model' => 'Bild Å 東京 😀']), $html);
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
     * `Trail::beforeSave()` stamps the time and the logged-in user, so the row is moved to another user and an
     * earlier time afterwards.
     */
    private function insertTrail(Redirect $redirect, int $type, ?DateTime $createdAt = null, ?int $userId = null): Trail
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model_class = Redirect::class;
        $trail->model_id = $redirect->id;

        self::assertTrue($trail->insert());

        $trail->updateAttributes(array_filter([
            'created_at' => $createdAt,
            'user_id' => $userId ?? $this->getUserFromFixture('admin')->id,
        ]));

        return $trail;
    }

    private function getHref(Redirect $redirect): string
    {
        $route = $redirect->getAdminRoute();
        self::assertNotFalse($route);

        return 'href="' . Url::to($route) . '"';
    }
}
