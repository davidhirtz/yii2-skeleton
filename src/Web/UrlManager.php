<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Console\Application as ConsoleApplication;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use InvalidArgumentException;
use Override;
use Yii;
use yii\web\UrlNormalizerRedirectException;
use yii\web\UrlRule;

class UrlManager extends \yii\web\UrlManager
{
    public const string EVENT_AFTER_CREATE = 'afterCreate';
    public const string EVENT_BEFORE_PARSE = 'beforeParse';

    /**
     * Further validation is the controller's.
     */
    public string|false $draftSubdomain = 'draft';

    /**
     * Off by default, so the draft content is protected by nothing but its host name unless the web server guards it.
     */
    public bool $draftRequiresLogin = false;

    public bool $i18nUrl = false;

    /**
     * Language ⇒ URL param (`['en-US' => 'en']`); `null` takes the I18N component's languages.
     *
     * @var array<string, string>|null
     */
    public ?array $languages = null;

    /**
     * Also the language without a path prefix under `i18nUrl`; `null` detects it from the browser.
     */
    public ?string $defaultLanguage = null;

    /**
     * @var array<string, array{url?: string, request?: string, code?: int}|string>
     * @see \Hirtz\Skeleton\Models\Redirect
     */
    public array $redirectMap = [];

    public $enablePrettyUrl = true;
    public $showScriptName = false;

    private ?string $configuredHostInfo = null;
    private bool $isBaseUrlConfigured = false;
    private bool $isHostInfoFromRequest = false;
    private bool $isInitialized = false;

    #[Override]
    public function init(): void
    {
        if ($this->configuredHostInfo === null && ($hostInfo = Yii::$app->params['hostInfo'] ?? null)) {
            $this->setHostInfo($hostInfo);

            // Yii refuses to guess the console's base URL; the pinned host is the installation's root
            if (!$this->isBaseUrlConfigured && Yii::$app instanceof ConsoleApplication) {
                $this->setBaseUrl('');
            }
        }

        $this->isInitialized = true;

        if (!$this->enablePrettyUrl) {
            $this->i18nUrl = false;
        }

        if (count($this->getLanguages()) < 2) {
            $this->i18nUrl = false;
        }

        parent::init();
    }

    /**
     * @return array<string, string>
     */
    public function getLanguages(): array
    {
        if ($this->languages === null) {
            $this->languages = [];

            foreach (Yii::$app->getI18n()->getLanguages() as $language) {
                $this->languages[$language] = strstr((string)$language, '-', true) ?: $language;
            }
        }

        return $this->languages;
    }

    /**
     * @param array<int|string, mixed>|string $params
     */
    #[Override]
    public function createUrl($params): string
    {
        $request = Request::current();
        $language = Yii::$app->language;

        $params = (array)$params;

        if ($this->i18nUrl) {
            $language = ArrayHelper::remove($params, $request->languageParam ?? 'language', $language);
            $defaultLanguage = ArrayHelper::remove($params, 'defaultLanguage');
        }

        $defaultLanguage ??= $this->defaultLanguage;

        $url = parent::createUrl(array_filter($params, fn ($value): bool => !is_null($value)));
        $event = $this->getAfterCreateEvent($url, $params);

        if ($event) {
            $url = $event->url;
        }

        if (!$language) {
            return $url;
        }

        $languages = $this->getLanguages();

        if ($this->i18nUrl && isset($languages[$language]) && $language !== $defaultLanguage) {
            $position = strlen($this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl());
            return rtrim(substr_replace($url, '/' . $languages[$language], $position, 0), '/');
        }

        return $url;
    }

    /**
     * An address the same for every request, for a service that stores it: no language prefix.
     *
     * @param array<int|string, mixed>|string $params
     */
    public function createCanonicalUrl(array|string $params): string
    {
        $params = (array)$params;

        if ($this->i18nUrl) {
            $params[Request::current()->languageParam ?? 'language'] = false;
        }

        return $this->createAbsoluteUrl($params);
    }

    /**
     * @param string|null $value
     */
    #[Override]
    public function setBaseUrl($value): void
    {
        parent::setBaseUrl($value);

        if (!$this->isInitialized) {
            $this->isBaseUrlConfigured = true;
        }
    }

    /**
     * A host set in the configuration is the canonical one: the request's never replaces it.
     *
     * @param string|null $value
     */
    #[Override]
    public function setHostInfo($value): void
    {
        parent::setHostInfo($value);

        if (!$this->isInitialized) {
            $this->configuredHostInfo = $value;
        }

        $this->isHostInfoFromRequest = false;
    }

    /**
     * True when nothing pinned a host, so absolute URLs name the one the request claimed.
     */
    public function isHostInfoFromRequest(): bool
    {
        return $this->isHostInfoFromRequest;
    }

    public function isHostInfoConfigured(): bool
    {
        return $this->configuredHostInfo !== null;
    }

    /**
     * @param array<int|string, mixed>|string $params
     */
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

    protected function replaceSubdomain(string $replacement, string $url): string
    {
        return preg_replace('#^((https?://)(www.)?)#', "$2$replacement.", $url);
    }

    /**
     * @param Request $request
     * @return array{string, array<string, mixed>}|false
     */
    #[Override]
    public function parseRequest($request): bool|array
    {
        $this->parseRedirectMap($request, $this->redirectMap);

        if ($this->configuredHostInfo === null) {
            $this->setHostInfo($request->getHostInfo());
            $this->isHostInfoFromRequest = true;
        }

        if ($this->draftSubdomain) {
            $this->setDraftStatus($request);
        }

        if (count($this->getLanguages()) > 1) {
            $this->setLanguage($request);
        }

        $event = $this->getBeforeParseEvent($request);

        if ($event) {
            $request = $event->request;
        }

        return parent::parseRequest($request);
    }

    /**
     * @param array<string, array{url?: string, request?: string, code?: int}|string> $redirectMap
     */
    protected function parseRedirectMap(Request $request, array $redirectMap): void
    {
        if ($redirectMap) {
            $pathInfo = trim($request->getPathInfo(), '/');
            $query = explode('?', (string)$request->getUrl(), 2)[1] ?? '';

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

                if (!str_contains($location, '://')) {
                    $location = '/' . ltrim($location, '/');
                }

                foreach ((array)$urlset as $url) {
                    $url = trim((string)$url, '/');

                    if ($url === $pathInfo) {
                        throw new UrlNormalizerRedirectException($this->appendQuery($location, $query), $statusCode);
                    }

                    if (str_contains($url, '*')) {
                        $prefix = strstr($url, '*', true);

                        if ($prefix !== false && str_starts_with($pathInfo, $prefix)) {
                            $newUrl = $location . substr($pathInfo, strlen($prefix));
                            throw new UrlNormalizerRedirectException($this->appendQuery($newUrl, $query), $statusCode);
                        }
                    }
                }
            }
        }
    }

    private function appendQuery(string $url, string $query): string
    {
        return $query !== '' ? $url . (str_contains($url, '?') ? '&' : '?') . $query : $url;
    }

    /**
     * The request's host tells a draft request, not the manager's: a configured host is always the live one.
     */
    protected function setDraftStatus(Request $request): void
    {
        if (str_contains((string)$request->getHostInfo(), "//$this->draftSubdomain.")) {
            $this->setHostInfo(str_replace("//$this->draftSubdomain.", '//', $this->getHostInfo()));
            $request->setIsDraft(true);
        }
    }

    protected function setLanguage(Request $request): void
    {
        if ($this->i18nUrl) {
            $originalPathInfo = ltrim($request->getPathInfo(), '/');
            $pathInfo = rtrim($originalPathInfo, '/');
            $code = explode('/', $pathInfo, 2)[0];

            // A whole first segment only: `de-stijl` is a slug, `DE` a URL nothing creates
            $language = array_search($code, $this->getLanguages(), true);

            if ($language !== false) {
                $request->setPathInfo(ltrim(mb_substr($pathInfo, mb_strlen($code)), '/'));

                if ($language === $this->defaultLanguage) {
                    throw new UrlNormalizerRedirectException($this->getUrlWithoutLanguage($request, $originalPathInfo, $code), 301);
                }

                Yii::$app->language = $language;
                return;
            }
        }

        Yii::$app->language = $this->defaultLanguage ?: $request->getPreferredLanguage(array_keys($this->getLanguages()));
    }

    private function getUrlWithoutLanguage(Request $request, string $pathInfo, string $code): string
    {
        $baseUrl = $request->getBaseUrl();
        [$path, $query] = [...explode('?', $request->getUrl(), 2), ''];
        $path = ltrim(substr($path, strlen($baseUrl)), '/');

        // A path tenant's segments precede the language: what the path info no longer holds. Counted in segments, as
        // Yii re-encodes a path info that is not UTF-8
        $segments = explode('/', $path);
        $count = substr_count(urldecode($path), '/') - substr_count($pathInfo, '/');
        $prefix = $count > 0 ? implode('/', array_slice($segments, 0, $count)) . '/' : '';
        $path = ltrim(substr(implode('/', array_slice($segments, $count)), strlen($code)), '/');

        $url = $path === '' ? rtrim($prefix, '/') : $prefix . $path;

        return $request->getHostInfo() . $baseUrl . '/' . $url . ($query !== '' ? "?$query" : '');
    }

    protected function getBeforeParseEvent(Request $request): ?UrlManagerEvent
    {
        $event = Yii::$container->get(UrlManagerEvent::class, [], ['request' => $request]);
        $this->trigger(static::EVENT_BEFORE_PARSE, $event);

        return $event;
    }

    /**
     * @param array<int|string, mixed> $params
     */
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
     * @param array<string, mixed> $ruleDeclarations
     */
    #[Override]
    protected function buildRules($ruleDeclarations): array
    {
        $orderedRuleDeclarations = [];
        $i = 1;

        foreach ($ruleDeclarations as $key => &$rule) {
            $orderedRuleDeclarations[$key] = ArrayHelper::remove($rule, 'position', $i++);
        }

        asort($orderedRuleDeclarations, SORT_NUMERIC);

        foreach ($orderedRuleDeclarations as $key => &$value) {
            $value = $ruleDeclarations[$key];
        }

        return parent::buildRules($orderedRuleDeclarations);
    }

    /**
     * @return list<string>
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
                        ...explode('|', $matches[1]),
                    ];
                }
            }
        }

        return array_values(array_unique($params));
    }

    public function getDraftHostInfo(): string
    {
        return $this->draftSubdomain
            ? $this->replaceSubdomain($this->draftSubdomain, $this->getHostInfo())
            : $this->getHostInfo();
    }
}
