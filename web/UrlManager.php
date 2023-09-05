<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\web\UrlRule;

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
     * @var bool whether the language should be added to the URL via `languageParam`.
     */
    public $i18nUrl = false;

    /**
     * @var bool whether the subdomain should be used as language identifier.
     */
    public $i18nSubdomain = false;

    /**
     * @var array containing the languages available for `i18nUrl` or `i18nSubdomain`, leave empty to use the languages
     * defined in `i18n` component.
     */
    public $languages;

    /**
     * @var string|false the default language for which no language identifier should be added to the path or subdomain.
     * Set to `false` to use the first matching language for the initial request.
     */
    public $defaultLanguage;

    /**
     * @var string
     */
    public $languageParam = 'language';

    /**
     * @var array containing hard redirects, either as request URI => URL pairs, which generate regular 301 redirects
     * or as arrays containing the request URIs at first position, the target URL as the second and an
     * optional third containing the redirect code (defaults to 301). If dynamic redirects are needed, please take
     * a look at {@link \davidhirtz\yii2\skeleton\models\Redirect}.
     */
    public $redirectMap = [];

    /**
     * Events.
     */
    public const EVENT_AFTER_CREATE = 'afterCreate';
    public const EVENT_BEFORE_PARSE = 'beforeParse';

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

        if (Yii::$app instanceof \davidhirtz\yii2\skeleton\console\Application) {
            $this->setBaseUrl(Yii::$app->params['baseUrl'] ?? '');
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
            $request = Yii::$app->getRequest();
            $subdomain = $language == $this->defaultLanguage || !in_array($language, $this->languages) ?
                ($request->getIsDraft() ? $request->draftSubdomain : 'www') :
                ($request->getIsDraft() ? ($request->draftSubdomain . '.' . $language) : $language);

            return parse_url($this->getHostInfo(), PHP_URL_SCHEME) . '://' . $subdomain . $this->getI18nHostInfo() . $event->url;
        }

        return $event->url;
    }

    /**
     * @param array $params
     * @return bool|string
     */
    public function createDraftUrl($params)
    {
        if ($hostInfo = Yii::$app->getRequest()->getDraftHostInfo()) {
            return $hostInfo . $this->createUrl($params);
        }

        return false;
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

                if (!str_contains($location, '://') && is_string($location)) {
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
            // Check if the pathInfo starts with a language identifier.
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
            } else {
                Yii::$app->language = $this->defaultLanguage ?: $request->getPreferredLanguage(array_keys($this->languages));
            }
        }

        if ($this->i18nSubdomain) {
            $subdomain = explode('.', parse_url($this->getHostInfo(), PHP_URL_HOST))[$request->getIsDraft() ? 1 : 0];
            Yii::$app->language = in_array($subdomain, $this->languages) ? $subdomain : $this->defaultLanguage;
        }

        $this->trigger(static::EVENT_BEFORE_PARSE, $event = new UrlManagerEvent([
            'request' => $request,
        ]));

        return parent::parseRequest($event->request);
    }

    /**
     * Generates a list of rule parameters at given position. This can be used to validate dynamic slugs, etc.
     * @param int $position
     * @return array
     */
    public function getImmutableRuleParams($position = 0)
    {
        $params = [];
        foreach (Yii::$app->getUrlManager()->rules as $rule) {
            if ($rule instanceof UrlRule) {
                $param = explode('/', $rule->name)[$position];
                if (preg_match('/^\w+$/', $param)) {
                    $params[] = $param;
                } elseif (preg_match('/^<\w+:([\w|]+)>$/', $param, $matches)) {
                    $params = array_merge($params, explode('|', $matches[1]));
                }
            }
        }

        return array_unique($params);
    }

    /**
     * @return bool|string
     */
    public function getI18nHostInfo()
    {
        $request = Yii::$app->getRequest();

        $hostInfo = Yii::$app->language == $this->defaultLanguage ?
            ($request->getIsDraft() ? $request->draftSubdomain : 'www') :
            ($request->getIsDraft() ? ($request->draftSubdomain . '.' . Yii::$app->language) : Yii::$app->language);

        return substr(parse_url($this->getHostInfo(), PHP_URL_HOST), strlen($hostInfo));
    }

    /**
     * @return bool
     */
    public function hasI18nUrls()
    {
        return $this->i18nUrl || $this->i18nSubdomain;
    }
}