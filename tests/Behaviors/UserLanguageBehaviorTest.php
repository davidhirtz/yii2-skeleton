<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Behaviors;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Behaviors\UserLanguageBehavior;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;

class UserLanguageBehaviorTest extends TestCase
{
    use UserFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testSetIdentityLanguage(): void
    {
        Yii::$app->getUser()->login(User::findOne(1));
        Yii::$app->getRequest()->setQueryParams(['language' => 'de']);

        $behavior = new UserLanguageBehavior();
        $behavior->setLanguage();

        $user = User::findOne(1);

        self::assertEquals('de', $user->language);
        self::assertEmpty($this->getLanguageCookieValue());
    }

    public function testGetIdentityLanguage(): void
    {
        User::updateAll(['language' => 'de'], ['id' => 1]);
        Yii::$app->getUser()->login(User::findOne(1));

        $behavior = new UserLanguageBehavior();
        $behavior->setApplicationLanguage = true;
        $behavior->setLanguage();

        self::assertEquals('de', Yii::$app->language);
    }

    public function testGetIdentityLanguageBeforeAction(): void
    {
        User::updateAll(['language' => 'de'], ['id' => 1]);
        Yii::$app->getUser()->login(User::findOne(1));

        $controller = new UserLanguageController('user-language', Yii::$app);
        $controller->runAction('index');

        self::assertEquals('de', Yii::$app->language);
    }

    public function testSetCookieLanguage(): void
    {
        Yii::$app->getRequest()->setQueryParams(['language' => 'de']);

        $behavior = new UserLanguageBehavior();
        $behavior->setLanguage();

        self::assertEquals('de', $this->getLanguageCookieValue());
    }

    protected function getLanguageCookieValue(): ?string
    {
        return Yii::$app->getResponse()->getCookies()->get(Yii::$app->getRequest()->languageParam)?->value;
    }
}

class UserLanguageController extends Controller
{
    #[Override]
    public function behaviors(): array
    {
        return [
            'UserLanguageBehavior' => UserLanguageBehavior::class,
        ];
    }

    public function actionIndex(): void
    {
    }
}
