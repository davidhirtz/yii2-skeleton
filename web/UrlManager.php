<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;

/**
 * Class UrlManager
 * @package davidhirtz\yii2\skeleton\web
 */
class UrlManager extends \yii\web\UrlManager
{
    /**
     * @var bool
     */
    public $enablePrettyUrl = true;

    /**
     * @var bool
     */
    public $enableStrictParsing = true;

    /**
     * @var bool
     */
    public $showScriptName = false;

    /**
     * @var bool
     */
    public $i18nUrl = false;

    /**
     * @var bool
     */
    public $i18nSubdomain = false;

    /**
     * @var array
     */
    public $languages;

    /**
     * @var string
     */
    public $defaultLanguage;

    /**
     * @var string
     */
    public $languageParam = 'language';

    /**
     * @var array
     */
    public $redirectMap = [];

    /**
     * Events.
     */
    const EVENT_AFTER_CREATE = 'afterCreate';
    const EVENT_BEFORE_PARSE = 'beforeParse';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->enablePrettyUrl) {
            $this->i18nUrl = false;
        }

        if ($this->i18nUrl) {
            $this->i18nSubdomain = false;
        }

        if ($this->defaultLanguage === null) {
            $this->defaultLanguage = Yii::$app->sourceLanguage;
        }

        if ($this->languages === null) {
            foreach (Yii::$app->getI18n()->getLanguages() as $language) {
                $this->languages[$language] = strstr($language, '-', true) ?: $language;
            }
        }

        if (!$this->languages) {
            $this->i18nUrl = false;
        }

        parent::init();

    }

    /**
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $language = Yii::$app->language;
        $i18nUrl = $this->i18nUrl;

        if (!empty($params['i18n'])) {
            $i18nUrl = $this->enablePrettyUrl;
            unset($params['i18n']);
        }

        if ($i18nUrl || $this->i18nSubdomain) {
            if (isset($params[$this->languageParam])) {
                $language = $params[$this->languageParam];
                unset($params[$this->languageParam]);
            }
        }

        $url = parent::createUrl(array_filter($params, function ($value) {
            return !is_null($value);
        }));

        $this->trigger(static::EVENT_AFTER_CREATE, $event = new UrlManagerEvent([
            'url' => $url,
            'params' => $params,
        ]));

        if ($i18nUrl) {
            if (isset($this->languages[$language]) && $language !== $this->defaultLanguage) {
                $position = strlen($this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl());
                return rtrim(substr_replace($event->url, '/' . $this->languages[$language], $position, 0), '/');
            }
        }

        if ($this->i18nSubdomain && $language !== Yii::$app->language) {
            $subdomain = $language == $this->defaultLanguage || !in_array($language, $this->languages) ? 'www' : $language;
            return parse_url($this->getHostInfo(), PHP_URL_SCHEME) . '://' . $subdomain . $this->getI18nHostInfo() . $event->url;
        }

        return $event->url;
    }

    /**
     * @param Request $request
     * @return array|bool
     */
    public function parseRequest($request)
    {
        $pathInfo = trim($request->getPathInfo(), '/');
        $response = Yii::$app->getResponse();

        if ($this->redirectMap) {
            foreach ($this->redirectMap as $urlset => $location) {
                $statusCode = 301;

                if (is_array($location)) {
                    if (isset($location[2]) && $location[2] == 302) {
                        $statusCode = $location[2];
                    }

                    $urlset = $location[0];
                    $location = $location[1];
                }

                if (strpos($location, '://') === false && is_string($location)) {
                    $location = '/' . ltrim($location, '/');
                }

                foreach ((array)$urlset as $url) {
                    $url = trim($url, '/');
                    $wildcard = strpos($url, '*');

                    if ($url == $pathInfo || ($wildcard && substr($url, 0, $wildcard) == substr($pathInfo, 0, $wildcard))) {
                        $response->redirect($location, $statusCode);
                        Yii::$app->end();
                    }
                }
            }
        }

        if ($this->i18nUrl) {
            if (preg_match('#^(' . implode('|', $this->languages) . ')\b(/?)#i', $pathInfo, $matches)) {
                $request->setPathInfo(mb_substr($pathInfo, mb_strlen($matches[0], Yii::$app->charset), null, Yii::$app->charset));
                $language = array_search($matches[1], $this->languages);

                if ($language) {
                    if ($language == $this->defaultLanguage) {
                        $response->redirect($request->getHostInfo() . '/' . $request->getPathInfo(), 301);
                        Yii::$app->end();
                    }

                    Yii::$app->language = $language;
                }
            } elseif ($this->defaultLanguage) {
                Yii::$app->language = $this->defaultLanguage;
            }
        }

        if($this->i18nSubdomain) {
            $subdomain = explode('.', parse_url($this->getHostInfo(), PHP_URL_HOST))[$request->getIsDraft() ? 1 : 0];
            Yii::$app->language = in_array($subdomain, $this->languages) ? $subdomain : $this->defaultLanguage;
        }

        $this->trigger(static::EVENT_BEFORE_PARSE, $event = new UrlManagerEvent([
            'request' => $request,
        ]));

        return parent::parseRequest($event->request);
    }

    /**
     * @return bool|string
     */
    public function getI18nHostInfo()
    {
        return substr(parse_url($this->getHostInfo(), PHP_URL_HOST), strlen(Yii::$app->language == $this->defaultLanguage ? 'www' : Yii::$app->language));
    }
}