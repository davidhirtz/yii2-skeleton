<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\web\UrlRule;

class UrlManager extends \yii\web\UrlManager
{
    public const EVENT_AFTER_CREATE = 'afterCreate';
    public const EVENT_BEFORE_PARSE = 'beforeParse';

    /**
     * @var bool whether the language should be added to the URL via `languageParam`.
     */
    public bool $i18nUrl = false;

    /**
     * @var bool whether the subdomain should be used as language identifier.
     */
    public bool $i18nSubdomain = false;

    /**
     * @var array|false|null containing the languages available for `i18nUrl` or `i18nSubdomain`, the language
     * identifier as key and the language param as value (e.g. ['en-US' ⇒ 'en']). Defaults to languages set in the
     * I18n component.
     */
    public array|false|null $languages = null;

    /**
     * @var string|false the default language for which no language identifier should be added to the path or subdomain.
     * Set to `false` to use the first matching language for the initial request.
     */
    public string|false|null $defaultLanguage = null;

    /**
     * @var array containing hard redirects, either as request URI ⇒ URL pairs, which generate regular 301 redirects
     * or as arrays containing the request URIs at first position, the target URL as the second and an
     * optional third containing the redirect code (defaults to 301). If dynamic redirects are necessary, please take
     * a look at {@see \davidhirtz\yii2\skeleton\models\Redirect}.
     */
    public array $redirectMap = [];

    public $enablePrettyUrl = true;
    public $showScriptName = false;

    public function init(): void
    {
        if (!$this->enablePrettyUrl) {
            $this->i18nUrl = false;
        }

        if ($this->i18nUrl) {
            $this->i18nSubdomain = false;
        }

        $this->defaultLanguage ??= Yii::$app->sourceLanguage;

        if ($this->languages === null) {
            $this->languages = [];

            foreach (Yii::$app->getI18n()->getLanguages() as $language) {
                $this->languages[$language] = strstr((string)$language, '-', true) ?: $language;
            }
        }

        if (!$this->languages) {
            $this->i18nUrl = false;
        }

        parent::init();
    }

    public function createUrl($params): string
    {
        $request = Yii::$app->getRequest();
        $language = Yii::$app->language;
        $i18nUrl = $this->i18nUrl;

        $params = (array)$params;

        if ($i18nUrl || $this->i18nSubdomain) {
            $language = ArrayHelper::remove($params, $request->languageParam, $language);
            $defaultLanguage = ArrayHelper::remove($params, 'defaultLanguage');
        }

        $defaultLanguage ??= $this->defaultLanguage;

        $url = parent::createUrl(array_filter($params, fn ($value): bool => !is_null($value)));

        $event = $this->getAfterCreateEvent($url, $params);
        $url = $event?->url ?? $url;

        if ($i18nUrl) {
            if (isset($this->languages[$language]) && $language !== $defaultLanguage) {
                $position = strlen($this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl());
                return rtrim(substr_replace($url, '/' . $this->languages[$language], $position, 0), '/');
            }
        }

        if ($this->i18nSubdomain && $language !== Yii::$app->language) {
            $subdomain = $language == $defaultLanguage || !in_array($language, $this->languages)
                ? ($request->getIsDraft() ? $request->draftSubdomain : 'www')
                : ($request->getIsDraft() ? ($request->draftSubdomain . '.' . $language) : $language);

            return parse_url($this->getHostInfo(), PHP_URL_SCHEME) . '://' . $subdomain . $this->getI18nHostInfo() . $url;
        }

        return $url;
    }

    public function createDraftUrl(array|string $params): string
    {
        if ($hostInfo = Yii::$app->getRequest()->getDraftHostInfo()) {
            return $hostInfo . $this->createUrl($params);
        }

        return '';
    }

    /**
     * @param Request $request
     */
    public function parseRequest($request): bool|array
    {
        $this->parseRedirectMap($request, $this->redirectMap);

        if (count($this->languages) > 1) {
            $this->setApplicationLanguage($request);
        }

        $event = $this->getBeforeParseEvent($request);

        return parent::parseRequest($event?->request ?? $request);
    }

    protected function parseRedirectMap(Request $request, array $redirectMap): void
    {
        if ($redirectMap) {
            $pathInfo = trim($request->getPathInfo(), '/');

            foreach ($redirectMap as $urlset => $location) {
                $statusCode = 301;

                if (is_array($location)) {
                    if (isset($location[2]) && $location[2] == 302) {
                        $statusCode = $location[2];
                    }

                    $urlset = $location[0];
                    $location = $location[1];
                }

                if (!str_contains((string)$location, '://') && is_string($location)) {
                    $location = '/' . ltrim($location, '/');
                }

                foreach ((array)$urlset as $url) {
                    $url = trim((string)$url, '/');
                    $wildcard = strpos($url, '*');

                    if ($url == $pathInfo || ($wildcard && substr($url, 0, $wildcard) == substr($pathInfo, 0, $wildcard))) {
                        Yii::$app->getResponse()->redirect($location, $statusCode);
                        Yii::$app->end();
                    }
                }
            }
        }
    }

    protected function setApplicationLanguage(Request $request): void
    {
        if ($this->i18nUrl) {
            // Check if the pathInfo starts with a language identifier.
            $pathInfo = trim($request->getPathInfo(), '/');

            if (preg_match('#^(' . implode('|', $this->languages) . ')\b(/?)#i', $pathInfo, $matches)) {
                $request->setPathInfo(mb_substr($pathInfo, mb_strlen($matches[0], Yii::$app->charset), null, Yii::$app->charset));
                $language = array_search($matches[1], $this->languages);

                if ($language) {
                    if ($language == $this->defaultLanguage) {
                        $url = preg_replace('#(/' . preg_quote($matches[1]) . ')(/|$)#', '$2', $request->getAbsoluteUrl());
                        Yii::$app->getResponse()->redirect($url, 301);
                        Yii::$app->end();
                    }

                    Yii::$app->language = $language;
                }

                return;
            }
        }

        if ($this->i18nSubdomain) {
            $host = parse_url($this->getHostInfo(), PHP_URL_HOST);
            $subdomain = explode('.', (string)$host)[$request->getIsDraft() ? 1 : 0];

            if (in_array($subdomain, $this->languages)) {
                Yii::$app->language = $subdomain;
                return;
            }
        }

        if (in_array($request->getLanguage(), $this->languages)) {
            Yii::$app->language = $request->getLanguage();
            return;
        }

        Yii::$app->language = $this->defaultLanguage ?: $request->getPreferredLanguage(array_keys($this->languages));
    }

    protected function getBeforeParseEvent(Request $request): ?UrlManagerEvent
    {
        $event = Yii::$container->get(UrlManagerEvent::class, [], ['request' => $request]);
        $this->trigger(static::EVENT_BEFORE_PARSE, $event);

        return $event;
    }

    protected function getAfterCreateEvent(string $url, array $params): ?UrlManagerEvent
    {
        $event = Yii::$container->get(UrlManagerEvent::class, [], [
            'url' => $url,
            'params' => $params,
        ]);

        $this->trigger(static::EVENT_AFTER_CREATE, $event);

        return $event;
    }

    /**
     * Generates a list of rule parameters at given position. This can be used to validate dynamic slugs, etc.
     */
    public function getImmutableRuleParams(int $position = 0): array
    {
        $params = [];

        foreach ($this->rules as $rule) {
            if ($rule instanceof UrlRule) {
                $param = explode('/', (string)$rule->name)[$position];

                if (preg_match('/^[\w_\-.]+$/', $param)) {
                    $params[] = $param;
                } elseif (preg_match('/^<\w+:\(?([\w_\-|]+)\)?>$/', $param, $matches)) {
                    $params = [
                        ...$params,
                        ...explode('|', $matches[1])
                    ];
                }
            }
        }

        return array_unique($params);
    }

    public function getI18nHostInfo(): string
    {
        $request = Yii::$app->getRequest();

        $hostInfo = Yii::$app->language == $this->defaultLanguage ?
            ($request->getIsDraft() ? $request->draftSubdomain : 'www') :
            ($request->getIsDraft() ? ($request->draftSubdomain . '.' . Yii::$app->language) : Yii::$app->language);

        return substr((string)parse_url($this->getHostInfo(), PHP_URL_HOST), strlen((string)$hostInfo));
    }

    public function hasI18nUrls(): bool
    {
        return $this->i18nUrl || $this->i18nSubdomain;
    }
}
