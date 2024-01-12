<?php

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
            if ($identity?->language != $language) {
                Yii::debug("Updating user language to $language");

                $identity->language = $language;
                $identity->update();
                return;
            }

            $isNewCookieLanguage = !in_array($language, [
                Yii::$app->getRequest()->getLanguageFromCookie(),
                Yii::$app->sourceLanguage,
            ]);

            if ($isNewCookieLanguage) {
                $cookie = Yii::$container->get(Cookie::class, [], [
                    'name' => Yii::$app->getRequest()->languageParam,
                    'value' => $language,
                ]);

                Yii::$app->getResponse()->getCookies()->add($cookie);
                return;
            }
        }

        if ($identity && !Yii::$app->getUrlManager()->hasI18nUrls()) {
            Yii::$app->language = $identity->language;
        }
    }
}
