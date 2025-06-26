<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use InvalidArgumentException;
use Yii;
use yii\web\UrlNormalizerRedirectException;
use yii\web\UrlRule;

class UrlManager extends \yii\web\UrlManager
{
    public const string EVENT_AFTER_CREATE = 'afterCreate';
    public const string EVENT_BEFORE_PARSE = 'beforeParse';

    /**
     * @var string|false the subdomain indicating a draft version of the application. Further validation should
     * be done on the controller level.
     */
    public string|false $draftSubdomain = 'draft';

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
     * or as arrays containing the request URIs as an array in array key `request`, the target URL as the key `url`
     * and optional the redirect code (defaults to 301) as `status`.
     *
     * If dynamic redirects are necessary, please take a look at {@see \davidhirtz\yii2\skeleton\models\Redirect}.
     */
    public array $redirectMap = [];

    public $enablePrettyUrl = true;
    public $showScriptName = false;

    #[\Override]
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

        if (count($this->languages) < 2) {
            $this->i18nUrl = false;
            $this->i18nSubdomain = false;
        }

        parent::init();
    }

    #[\Override]
    public function createUrl($params): string
    {
        $request = Yii::$app->getRequest();
        $language = Yii::$app->language;

        $params = (array)$params;

        if ($this->i18nUrl || $this->i18nSubdomain) {
            $language = ArrayHelper::remove($params, $request->languageParam, $language);
            $defaultLanguage = ArrayHelper::remove($params, 'defaultLanguage');
        }

        $defaultLanguage ??= $this->defaultLanguage;

        $url = parent::createUrl(array_filter($params, fn ($value): bool => !is_null($value)));
        $event = $this->getAfterCreateEvent($url, $params);

        if ($event) {
            $url = $event->url;
        }

        if ($this->i18nUrl) {
            if (isset($this->languages[$language]) && $language !== $defaultLanguage) {
                $position = strlen($this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl());
                return rtrim(substr_replace($url, '/' . $this->languages[$language], $position, 0), '/');
            }
        }

        if ($this->i18nSubdomain && $language !== $this->defaultLanguage) {
            $subdomain = $this->languages[$language] ?? '';
            return $this->replaceSubdomain($subdomain, $this->getHostInfo()) . $url;
        }

        return $url;
    }

    public function createDraftUrl(array|string $params): string
    {
        if ($this->draftSubdomain) {
            $url = $this->createUrl($params);

            return str_starts_with($url, 'http')
                ? $this->replaceSubdomain($this->draftSubdomain, $url)
                : $this->getDraftHostInfo() . $url;
        }

        return $this->createAbsoluteUrl($params);
    }

    private function replaceSubdomain(string $replacement, string $url): string
    {
        return preg_replace('#^((https?://)(www.)?)#', "$2$replacement.", $url);
    }

    /**
     * @param Request $request
     */
    #[\Override]
    public function parseRequest($request): bool|array
    {
        $this->parseRedirectMap($request, $this->redirectMap);
        $this->setHostInfo($request->getHostInfo());

        if ($this->draftSubdomain) {
            $this->setDraftStatus($request);
        }

        if (count($this->languages) > 1) {
            $this->setApplicationLanguage($request);
        }

        $event = $this->getBeforeParseEvent($request);

        if ($event) {
            $request = $event->request;
        }

        return parent::parseRequest($request);
    }

    protected function parseRedirectMap(Request $request, array $redirectMap): void
    {
        if ($redirectMap) {
            $pathInfo = trim($request->getPathInfo(), '/');

            foreach ($redirectMap as $urlset => $location) {
                $statusCode = 301;

                if (is_array($location)) {
                    if (!isset($location['url'])) {
                        throw new InvalidArgumentException('Missing location key in redirect map.');
                    }

                    $urlset = $location['request'] ?? $urlset;
                    $statusCode = $location['code'] ?? $statusCode;
                    $location = $location['url'];
                }

                if (!str_contains((string)$location, '://') && is_string($location)) {
                    $location = '/' . ltrim($location, '/');
                }

                foreach ((array)$urlset as $url) {
                    $url = trim((string)$url, '/');

                    if ($url === $pathInfo) {
                        throw new UrlNormalizerRedirectException($location, $statusCode);
                    }

                    if (str_contains($url, '*')) {
                        $prefix = strstr($url, '*', true);

                        if ($prefix !== false && str_starts_with($pathInfo, $prefix)) {
                            $newUrl = $location . substr($pathInfo, strlen($prefix));
                            throw new UrlNormalizerRedirectException($newUrl, $statusCode);
                        }
                    }
                }
            }
        }
    }

    protected function setDraftStatus(Request $request): void
    {
        $hostInfo = $this->getHostInfo();

        if (str_contains($hostInfo, "//$this->draftSubdomain.")) {
            $this->setHostInfo(str_replace("//$this->draftSubdomain.", '//', $hostInfo));
            $request->setIsDraft(true);
        }
    }

    protected function setApplicationLanguage(Request $request): void
    {
        if ($this->i18nUrl) {
            $pathInfo = trim($request->getPathInfo(), '/');

            if (preg_match('#^(' . implode('|', $this->languages) . ')\b(/?)#i', $pathInfo, $matches)) {
                $request->setPathInfo(mb_substr($pathInfo, mb_strlen($matches[0], Yii::$app->charset), null, Yii::$app->charset));
                $language = array_search($matches[1], $this->languages);

                if ($language) {
                    if ($language == $this->defaultLanguage) {
                        $url = preg_replace('#(/' . preg_quote($matches[1]) . ')(/|$)#', '$2', $request->getAbsoluteUrl());
                        throw new UrlNormalizerRedirectException($url, 301);
                    }

                    Yii::$app->language = $language;
                }

                return;
            }
        }

        if ($this->i18nSubdomain) {
            $host = parse_url($this->getHostInfo(), PHP_URL_HOST);
            $subdomain = explode('.', (string)$host)[0];

            if (in_array($subdomain, $this->languages)) {
                $replace = $this->languages[$this->defaultLanguage] ?? '';
                $this->setHostInfo(str_replace("//$subdomain", "//$replace", $this->getHostInfo()));
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

    public function getDraftHostInfo(): false|string
    {
        return $this->replaceSubdomain($this->draftSubdomain, $this->getHostInfo());
    }

    public function hasI18nUrls(): bool
    {
        return $this->i18nUrl || $this->i18nSubdomain;
    }
}
