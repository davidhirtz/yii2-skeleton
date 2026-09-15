<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class RedirectControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testIndexListsTheRedirects(): void
    {
        $this->login();
        $this->createRedirect('old-page', 'new-page');

        $html = Yii::$app->runAction('admin/redirect/index');

        self::assertIsString($html);
        self::assertStringContainsString('old-page', $html);
        self::assertStringContainsString('new-page', $html);
    }

    public function testIndexFiltersByType(): void
    {
        $this->login();
        $this->createRedirect('permanent', 'target-1');
        $this->createRedirect('temporary', 'target-2', Redirect::TYPE_FOUND);

        $html = Yii::$app->runAction('admin/redirect/index', ['type' => Redirect::TYPE_FOUND]);

        self::assertIsString($html);
        self::assertStringContainsString('temporary', $html);
        self::assertStringNotContainsString('permanent', $html);
    }

    public function testIndexFiltersBySearchTerm(): void
    {
        $this->login();
        $this->createRedirect('needle', 'target-1');
        $this->createRedirect('haystack', 'target-2');

        $html = Yii::$app->runAction('admin/redirect/index', ['q' => 'needl']);

        self::assertIsString($html);
        self::assertStringContainsString('needle', $html);
        self::assertStringNotContainsString('haystack', $html);
    }

    public function testIndexFiltersByTheUserThatLastChangedTheRedirect(): void
    {
        $user = $this->login();

        $this->createRedirect('mine', 'target-1');

        $other = $this->createRedirect('theirs', 'target-2');
        $other->updateAttributes(['updated_by_user_id' => $this->getUserFromFixture('disabled')->id]);

        $html = Yii::$app->runAction('admin/redirect/index', ['user' => $user->id]);

        self::assertIsString($html);
        self::assertStringContainsString('mine', $html);
        self::assertStringNotContainsString('theirs', $html);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/redirect/index');
    }

    public function testCreateRendersTheForm(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/redirect/create');

        self::assertIsString($html);
        self::assertStringContainsString('name="Redirect[request_uri]"', $html);
        self::assertStringContainsString('name="Redirect[url]"', $html);
    }

    public function testCreateInsertsTheRedirect(): void
    {
        $this->login();

        $response = $this->post('admin/redirect/create', [
            'Redirect' => [
                'request_uri' => '/old-page/',
                'url' => '/new-page',
            ],
        ]);

        self::assertInstanceOf(Response::class, $response);

        $redirect = Redirect::findOne(['request_uri' => 'old-page']);
        self::assertNotNull($redirect);

        // `Helpers\Url::sanitize()` strips the surrounding slashes off both ends
        self::assertSame('new-page', $redirect->url);
        self::assertSame(Redirect::TYPE_MOVED_PERMANENTLY, $redirect->type);
    }

    public function testCreateKeepsTheTypeFromTheQuery(): void
    {
        $this->login();

        $this->post('admin/redirect/create', [
            'Redirect' => [
                'request_uri' => 'old-page',
                'url' => 'new-page',
            ],
        ], ['type' => Redirect::TYPE_FOUND]);

        self::assertSame(Redirect::TYPE_FOUND, Redirect::findOne(['request_uri' => 'old-page'])->type);
    }

    public function testCreateRendersTheErrorsOfAnInvalidRedirect(): void
    {
        $this->login();

        $html = $this->post('admin/redirect/create', [
            'Redirect' => ['request_uri' => ''],
        ]);

        self::assertIsString($html);
        self::assertSame(0, Redirect::find()->count());
    }

    public function testCreateRefusesADuplicateRequestUri(): void
    {
        $this->login();
        $this->createRedirect('old-page', 'new-page');

        $html = $this->post('admin/redirect/create', [
            'Redirect' => [
                'request_uri' => 'old-page',
                'url' => 'other-page',
            ],
        ]);

        self::assertIsString($html);
        self::assertSame(1, Redirect::find()->count());
    }

    public function testUpdateRendersTheRedirect(): void
    {
        $this->login();
        $redirect = $this->createRedirect('old-page', 'new-page');

        $html = Yii::$app->runAction('admin/redirect/update', ['id' => $redirect->id]);

        self::assertIsString($html);
        self::assertStringContainsString('value="old-page"', $html);
    }

    public function testUpdateSavesTheRedirect(): void
    {
        $this->login();
        $redirect = $this->createRedirect('old-page', 'new-page');

        $response = $this->post('admin/redirect/update', [
            'Redirect' => [
                'request_uri' => 'old-page',
                'url' => 'newer-page',
            ],
        ], ['id' => $redirect->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('newer-page', Redirect::findOne($redirect->id)->url);
    }

    public function testUpdateRefusesARedirectToItself(): void
    {
        $this->login();
        $redirect = $this->createRedirect('old-page', 'new-page');

        $html = $this->post('admin/redirect/update', [
            'Redirect' => [
                'request_uri' => 'old-page',
                'url' => 'old-page',
            ],
        ], ['id' => $redirect->id]);

        self::assertIsString($html);
        self::assertSame('new-page', Redirect::findOne($redirect->id)->url);
    }

    public function testUpdateOfAnUnknownRedirectIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/redirect/update', ['id' => 99999]);
    }

    public function testDeleteRemovesTheRedirect(): void
    {
        $this->login();
        $redirect = $this->createRedirect('old-page', 'new-page');

        $response = $this->post('admin/redirect/delete', [], ['id' => $redirect->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertNull(Redirect::findOne($redirect->id));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testDeleteAllRemovesTheSelectedRedirects(): void
    {
        $this->login();

        $first = $this->createRedirect('first', 'target-1');
        $second = $this->createRedirect('second', 'target-2');
        $third = $this->createRedirect('third', 'target-3');

        $this->post('admin/redirect/delete-all', [
            'selection' => [(string)$first->id, (string)$second->id],
        ]);

        self::assertNull(Redirect::findOne($first->id));
        self::assertNull(Redirect::findOne($second->id));
        self::assertNotNull(Redirect::findOne($third->id));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testDeleteAllWithoutASelectionChangesNothing(): void
    {
        $this->login();
        $redirect = $this->createRedirect('old-page', 'new-page');

        $this->post('admin/redirect/delete-all', []);

        self::assertNotNull(Redirect::findOne($redirect->id));
        self::assertEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    private function createRedirect(
        string $requestUri,
        string $url,
        int $type = Redirect::TYPE_MOVED_PERMANENTLY,
    ): Redirect {
        $redirect = Redirect::create();
        $redirect->type = $type;
        $redirect->request_uri = $requestUri;
        $redirect->url = $url;

        self::assertTrue($redirect->insert());

        return $redirect;
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $bodyParams, array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Redirect::AUTH_REDIRECT);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
