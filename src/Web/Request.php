<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;
use Yii;

class Request extends \yii\web\Request
{
    final public const string ENVIRONMENT_LOCAL = 'local';
    final public const string ENVIRONMENT_STAGE = 'stage';

    /**
     * Matched with `fnmatch()`; a host matching none is production.
     *
     * @var array<string, list<string>>
     */
    public array $environments = [
        self::ENVIRONMENT_LOCAL => ['localhost', '*.localhost'],
        self::ENVIRONMENT_STAGE => ['stage.*', '*.stage.*'],
    ];

    /**
     * Without it a link built from the request (a mailed password reset) names whatever host the request claimed.
     *
     * @var list<string>
     */
    public array $allowedHosts = [];

    /**
     * Also switches the admin's session language ({@see \Hirtz\Skeleton\Modules\Admin\Module::beforeAction()}).
     */
    public string $languageParam = 'language';

    /**
     * Set by {@see \Hirtz\Skeleton\Widgets\Forms\Fields\Field::reloadsForm()}.
     */
    public string $formReloadHeader = 'X-Form-Reload';

    private bool $isDraft = false;

    /**
     * `null` under the console, so shared code (a mail template, a collection) never assumes a web request.
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
     * `config/params.php` holds scalars only, so the parameter is a comma-separated list; a hand-written array works.
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
        $host = $this->getHostName();

        // `parse_url()` keeps a backslash in the host, which a browser reads as the end of it (#487).
        if ($host !== null && !$this->isValidHostName($host)) {
            return false;
        }

        if (!$this->allowedHosts || $this->getEnvironment() === self::ENVIRONMENT_LOCAL) {
            return true;
        }

        if ($host !== null) {
            foreach ($this->allowedHosts as $pattern) {
                if (fnmatch($pattern, $host)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isValidHostName(string $host): bool
    {
        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * A body parser decoding into objects would hand `Model::load()` an object, which it rejects.
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
     * `$_COOKIE` keeps only the first of two cookies of one name, so only the raw header lists both (#195).
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
            self::ENVIRONMENT_LOCAL => Yii::t('skeleton', 'REQUEST_ENVIRONMENT_LOCAL_NAME'),
            self::ENVIRONMENT_STAGE => Yii::t('skeleton', 'REQUEST_ENVIRONMENT_STAGE_NAME'),
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
