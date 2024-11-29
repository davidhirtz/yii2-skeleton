<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Controller;
use yii\web\Cookie;

/**
 * Sets the application language from user identity or updates the user language based on the request language by
 * updating the user record or setting a cookie for guests.
 */
class UserLanguageBehavior extends Behavior
{
    /**
     * @var bool whether to set the application language from the user identity. If `null`, the application language
     * is only set if the language was not already set by the URL Manager.
     */
    public ?bool $setApplicationLanguage = null;

    public function init(): void
    {
        $this->setApplicationLanguage ??= !Yii::$app->getUrlManager()->hasI18nUrls();
        parent::init();
    }

    public function events(): array
    {
        return [
            Controller::EVENT_BEFORE_ACTION => $this->setLanguage(...),
        ];
    }

    public function setLanguage(): void
    {
        $identity = Yii::$app->getUser()->getIdentity();
        $language = Yii::$app->getRequest()->getLanguage();

        if ($language) {
            $this->saveLanguage($language);
        }

        if ($identity && $this->setApplicationLanguage) {
            Yii::$app->language = $identity->language;
        }
    }

    protected function saveLanguage(string $language): void
    {
        $identity = Yii::$app->getUser()->getIdentity();
        $request = Yii::$app->getRequest();

        if ($identity && $identity->language != $language) {
            Yii::debug("Updating user language to '$language' ...");

            $identity->language = $language;
            $identity->update();

            Yii::$app->getResponse()->getCookies()->remove($request->languageParam);

            return;
        }

        $isNewCookieLanguage = !in_array($language, [
            $request->getLanguageFromCookie(),
            Yii::$app->sourceLanguage,
        ]);

        if ($isNewCookieLanguage) {
            Yii::debug("Language cookie set to '$language'");

            $cookie = Yii::$container->get(Cookie::class, [], [
                'name' => $request->languageParam,
                'value' => $language,
            ]);

            Yii::$app->getResponse()->getCookies()->add($cookie);
        }
    }
}
