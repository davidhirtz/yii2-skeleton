<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class UserTrailControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testIndexListsOnlyTheTrailsOfThatUser(): void
    {
        $actor = $this->login();
        $other = $this->getUserFromFixture('disabled');

        $this->createTrail($actor, 'by the actor');
        $this->createTrail($other, 'by someone else');

        $html = Yii::$app->runAction('admin/user-trail/index', ['id' => $actor->id]);

        self::assertIsString($html);
        self::assertStringContainsString('by the actor', $html);
        self::assertStringNotContainsString('by someone else', $html);
    }

    /**
     * The provider populates the relation from the user it was scoped to, so the grid must not query it per row.
     */
    public function testIndexDoesNotQueryTheUserOfEveryRow(): void
    {
        $actor = $this->login();

        $this->createTrail($actor, 'first');
        $this->createTrail($actor, 'second');
        $this->createTrail($actor, 'third');

        $queries = $this->countQueries(function () use ($actor): void {
            Yii::$app->runAction('admin/user-trail/index', ['id' => $actor->id]);
        });

        self::assertLessThan(10, $queries);
    }

    public function testIndexOfAnUnknownUserIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/user-trail/index', ['id' => 99999]);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        $user = $this->getUserFromFixture('admin');
        Yii::$app->getUser()->setIdentity($user);

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-trail/index', ['id' => $user->id]);
    }

    private function createTrail(User $user, string $message): Trail
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_DEFAULT;
        $trail->model_class = User::class;
        $trail->model_id = (string)$user->id;
        $trail->message = $message;

        self::assertTrue($trail->insert());

        // `Trail::beforeSave()` stamps the acting identity, so the author is set afterwards
        $trail->updateAttributes(['user_id' => $user->id]);

        return $trail;
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Trail::AUTH_TRAIL_INDEX);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
