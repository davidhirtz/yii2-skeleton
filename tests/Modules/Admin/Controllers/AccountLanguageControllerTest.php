<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\Response;

/**
 * The navbar and `<html lang>` stand outside every swap, so a changed account language reloads the page.
 */
class AccountLanguageControllerTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testChangingTheAccountLanguageReloadsThePage(): void
    {
        $user = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($user);

        $response = $this->post('admin/account/update', [], ['User' => ['language' => 'de']]);

        self::assertSame('de', User::findOne($user->id)?->language);
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($this->prepare($response)->getHeaders()->has('HX-Refresh'));
    }

    public function testChangingTheOwnLanguageInUsersReloadsThePageInIt(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER);
        $this->getWebUser()->setIdentity($user);

        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);
        $module->setSessionLanguage('en-US');

        $response = $this->post('admin/user/update', ['id' => $user->id], ['User' => ['language' => 'de']]);

        self::assertSame('de', User::findOne($user->id)?->language);
        self::assertNull($module->getSessionLanguage());
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($this->prepare($response)->getHeaders()->has('HX-Refresh'));
    }

    /**
     * The headers of a reload are written as the response is sent.
     */
    private function prepare(Response $response): Response
    {
        (fn () => $this->prepare())->call($response);
        return $response;
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $bodyParams
     */
    private function post(string $route, array $params, array $bodyParams): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->getHeaders()->set('HX-Request', 'true');
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }
}
