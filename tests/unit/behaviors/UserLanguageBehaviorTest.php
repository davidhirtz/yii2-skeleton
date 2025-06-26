<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\UserLanguageBehavior;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;

class UserLanguageBehaviorTest extends Unit
{
    use UserFixtureTrait;

    protected UnitTester $tester;

    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        parent::_before();
    }

    public function testSetIdentityLanguage()
    {
        $this->tester->amLoggedInAs(1);
        Yii::$app->getRequest()->setQueryParams(['language' => 'de']);

        $behavior = new UserLanguageBehavior();
        $behavior->setLanguage();

        $user = User::findOne(1);

        self::assertEquals('de', $user->language);
        self::assertEmpty($this->getLanguageCookieValue());
    }

    public function testGetIdentityLanguage()
    {
        User::updateAll(['language' => 'de'], ['id' => 1]);
        $this->tester->amLoggedInAs(1);

        $behavior = new UserLanguageBehavior();
        $behavior->setApplicationLanguage = true;
        $behavior->setLanguage();

        self::assertEquals('de', Yii::$app->language);
    }

    public function testGetIdentityLanguageBeforeAction()
    {
        User::updateAll(['language' => 'de'], ['id' => 1]);
        $this->tester->amLoggedInAs(1);

        $controller = new UserLanguageController('user-language', Yii::$app);
        $controller->runAction('index');

        self::assertEquals('de', Yii::$app->language);
    }

    public function testSetCookieLanguage()
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
    #[\Override]
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
