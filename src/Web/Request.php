<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;
use Yii;

/**
 * The web Request class extends the default Yii class by a draft mode, environments and allowed hosts.
 */
class Request extends \yii\web\Request
{
    final public const string ENVIRONMENT_LOCAL = 'local';
    final public const string ENVIRONMENT_STAGE = 'stage';

    /**
     * @var array<string, list<string>> the host names per environment, matched with `fnmatch()`. A host that matches
     * none of them is the production environment; a project replaces or extends the list under `components.request`.
     */
    public array $environments = [
        self::ENVIRONMENT_LOCAL => ['localhost', '*.localhost'],
        self::ENVIRONMENT_STAGE => ['stage.*', '*.stage.*'],
    ];

    /**
     * @var list<string> the host names a request may carry, matched with `fnmatch()` like {@see $environments};
     * anything else is refused before routing. Empty means any host. Defaults to `params.allowedHosts`. A local host
     * always passes. Without it, a link built from the request — a password reset mailed to a user — names whatever
     * host the request claimed.
     */
    public array $allowedHosts = [];

    /**
     * @var string the parameter name used to add the language to a URL via `UrlManager::$i18nUrl` and to switch the
     * session language of the admin, see {@see \Hirtz\Skeleton\Modules\Admin\Module::beforeAction()}.
     */
    public string $languageParam = 'language';

    /**
     * @var string the header a form reload is marked with by {@see \Hirtz\Skeleton\Widgets\Forms\Fields\Field::reloadsForm()}.
     */
    public string $formReloadHeader = 'X-Form-Reload';

    private bool $isDraft = false;

    /**
     * Both applications answer a request, so shared code — a mail template rendering a form, a collection reading
     * a query parameter — asks for the web one instead of assuming the request carries a query string at all.
     */
    public static function current(): ?static
    {
        $request = Yii::$app->getRequest();
        return $request instanceof static ? $request : null;
    }

    #[Override]
    public function init(): void
    {
        if ($this->enableCookieValidation && !$this->cookieValidationKey) {
            $this->cookieValidationKey = Yii::$app->params['cookieValidationKey'] ?? '';
        }

        if (!$this->allowedHosts) {
            $this->allowedHosts = $this->parseAllowedHosts(Yii::$app->params['allowedHosts'] ?? null);
        }

        parent::init();
    }

    /**
     * `config/params.php` holds scalars only — `./yii params` and the config admin compare and print them as
     * strings — so the parameter is a comma-separated list; an array written by hand is taken as well.
     *
     * @return list<string>
     */
    protected function parseAllowedHosts(mixed $hosts): array
    {
        $hosts = is_string($hosts) ? explode(',', $hosts) : (is_array($hosts) ? $hosts : []);
        $hosts = array_map(static fn (mixed $host): string => is_string($host) ? trim($host) : '', $hosts);

        return array_values(array_filter($hosts));
    }

    public function addAllowedHosts(string ...$hosts): void
    {
        $this->allowedHosts = array_values(array_unique([...$this->allowedHosts, ...$hosts]));
    }

    public function isAllowedHost(): bool
    {
        if (!$this->allowedHosts || $this->getEnvironment() === self::ENVIRONMENT_LOCAL) {
            return true;
        }

        $host = $this->getHostName();

        if ($host !== null) {
            foreach ($this->allowedHosts as $pattern) {
                if (fnmatch($pattern, $host)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A body parser configured to decode into objects makes `yii\web\Request` answer one, which `Model::load()`
     * rejects — every call site would have to handle it, so the shape is settled here instead.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function getBodyParams(): array
    {
        $params = parent::getBodyParams();

        return is_object($params) ? get_object_vars($params) : $params;
    }

    /**
     * @param string|null $name
     * @param mixed $defaultValue
     * @return ($name is null ? array<string, mixed> : mixed)
     */
    #[Override]
    public function post($name = null, $defaultValue = null)
    {
        return $name === null ? $this->getBodyParams() : parent::post($name, $defaultValue);
    }

    /**
     * A form reload posts to the same action as the save, so an action that writes must skip the write for it.
     */
    public function isFormReload(): bool
    {
        return $this->getHeaders()->has($this->formReloadHeader);
    }

    /**
     * PHP collapses two cookies of one name into the first the browser sent, so `$_COOKIE` — and with it
     * {@see getCookies()} — cannot see a duplicate at all. Only the raw header lists both (monorepo issue #195).
     *
     * @return list<string>
     */
    public function getDuplicateCookieNames(): array
    {
        $counts = [];

        foreach (explode(';', (string)($_SERVER['HTTP_COOKIE'] ?? '')) as $cookie) {
            $name = trim(strstr($cookie, '=', true) ?: $cookie);

            if ($name !== '') {
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
    }

    public function getIsAjaxRoute(): bool
    {
        return $this->getIsAjax() && ($_SERVER['HTTP_X_AJAX_REQUEST'] ?? null) === 'route';
    }

    public function isDraftRequest(): bool
    {
        $subdomain = Yii::$app->getUrlManager()->draftSubdomain;
        return $subdomain && str_contains((string)$this->getHostInfo(), "//$subdomain.");
    }

    public function getEnvironment(): ?string
    {
        $host = $this->getHostName();

        if ($host !== null) {
            foreach ($this->environments as $environment => $hosts) {
                foreach ($hosts as $pattern) {
                    if (fnmatch($pattern, $host)) {
                        return $environment;
                    }
                }
            }
        }

        return null;
    }

    public function getEnvironmentName(): ?string
    {
        return match ($environment = $this->getEnvironment()) {
            self::ENVIRONMENT_LOCAL => Yii::t('skeleton', 'REQUEST_ENVIRONMENT_LOCAL'),
            self::ENVIRONMENT_STAGE => Yii::t('skeleton', 'REQUEST_ENVIRONMENT_STAGE'),
            default => $environment,
        };
    }

    public function isHtmxRequest(): bool
    {
        return $this->getHeaders()->has('HX-Request');
    }

    public function getIsDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): void
    {
        $this->isDraft = $isDraft;
    }

    public function preferNoContent(): bool
    {
        $prefer = (string)$this->getHeaders()->get('prefer');
        return str_contains(strtolower($prefer), 'status=204');
    }
}
