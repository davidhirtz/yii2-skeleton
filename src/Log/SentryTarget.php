<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log;

use Closure;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Log\Traits\MaskQueryParamsTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\AbstractErrorListenerIntegration;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\UserDataBag;
use Throwable;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

/**
 * Added beside the file log by `Base\Traits\ApplicationTrait` only when `params.sentryDsn` is set.
 */
class SentryTarget extends Target
{
    use MaskQueryParamsTrait;

    public ?string $dsn = null;

    /**
     * Both resolve from the property, then `SENTRY_ENVIRONMENT` / `SENTRY_RELEASE`, then a default of ours.
     */
    public ?string $environment = null;
    public ?string $release = null;

    /**
     * @see https://docs.sentry.io/platforms/php/configuration/options/
     * @var array<string, mixed>
     */
    public array $clientOptions = [];

    /**
     * Empty, since the context dump's masking is meant for a local file rather than an external service.
     *
     * @var array<array-key, string>
     */
    public $logVars = [];

    private ?HubInterface $hub = null;

    #[Override]
    public function init(): void
    {
        if (!$this->dsn) {
            throw new InvalidConfigException(static::class . ' needs a DSN. Set `sentryDsn` in `config/params.php`.');
        }

        parent::init();
    }

    #[Override]
    public function export(): void
    {
        $hub = $this->getHub();

        foreach ($this->messages as $message) {
            $hub->withScope(fn (Scope $scope) => $this->capture($hub, $scope, $message));
        }
    }

    /**
     * @param array{0: mixed, 1: int, 2: string, 3: float, 4: array<mixed>, 5?: int} $message
     */
    protected function capture(HubInterface $hub, Scope $scope, array $message): void
    {
        [$text, $level, $category] = $message;

        $scope->setTag('category', $category);

        // On the scope rather than on the capture: `captureException()` names no level of its own, so an
        // exception logged as a warning would arrive as the `error` Sentry defaults a level-less event to.
        $scope->setLevel($this->getSeverity($level));

        if ($user = $this->getUser()) {
            $scope->setUser($user);
        }

        if ($text instanceof Throwable) {
            $hub->captureException($text);
            return;
        }

        $hub->captureMessage(is_string($text) ? $text : VarDumper::export($text));
    }

    /**
     * The id alone, read without renewing the identity, so a report never opens a session of its own.
     */
    protected function getUser(): ?UserDataBag
    {
        $id = WebUser::current()?->getIdentity(false)?->getId();
        return $id === null ? null : UserDataBag::createFromUserIdentifier($id);
    }

    protected function getSeverity(int $level): Severity
    {
        return match ($level) {
            Logger::LEVEL_ERROR => Severity::error(),
            Logger::LEVEL_WARNING => Severity::warning(),
            Logger::LEVEL_INFO => Severity::info(),
            default => Severity::debug(),
        };
    }

    /**
     * Set rather than read, since Sentry's process-wide hub may hold a client another application left behind.
     */
    protected function getHub(): HubInterface
    {
        return $this->hub ??= SentrySdk::setCurrentHub(
            new Hub(ClientBuilder::create($this->getClientOptions())->getClient()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getClientOptions(): array
    {
        return [
            'dsn' => $this->dsn,
            // The middle term of each is Sentry's own default, repeated because naming the key at all shadows it
            // — and a deploy pipeline setting `SENTRY_RELEASE` is the one that can associate commits.
            'environment' => $this->environment ?? $_SERVER['SENTRY_ENVIRONMENT'] ?? YII_ENV,
            'release' => $this->release ?? $_SERVER['SENTRY_RELEASE'] ?? $this->getDefaultRelease(),
            // The posted body is what `maskVars` keeps out of the file log, and no mask of ours reaches it here.
            'max_request_body_size' => 'none',
            'send_default_pii' => false,
            // Yii's error handler reports through this target already. Sentry's own listeners would report a
            // second time and take over the handler that renders the error page.
            'integrations' => static fn (array $integrations): array => array_values(array_filter(
                $integrations,
                static fn (IntegrationInterface $integration): bool => !$integration instanceof AbstractErrorListenerIntegration,
            )),
            ...$this->clientOptions,
            // Last, and both composed rather than replaced: a project adding a tag or a callback of its own must
            // not drop the project's name or the masking.
            'tags' => [...$this->getTags(), ...$this->clientOptions['tags'] ?? []],
            'before_send' => $this->getBeforeSend(),
        ];
    }

    /**
     * Masks the URL tokens `RequestIntegration` sends whatever `send_default_pii` says (#166); a project's callback
     * runs after.
     */
    protected function getBeforeSend(): Closure
    {
        $callback = $this->clientOptions['before_send'] ?? null;

        return function (Event $event, ?EventHint $hint = null) use ($callback): ?Event {
            $request = $event->getRequest();

            foreach (['url', 'query_string'] as $key) {
                if (is_string($request[$key] ?? null)) {
                    $request[$key] = $this->maskQueryParamValues($request[$key]);
                }
            }

            $event->setRequest($request);

            return is_callable($callback) ? $callback($event, $hint) : $event;
        };
    }

    /**
     * Sentry's `package@version` convention, the abbreviated deployed commit as the version.
     */
    protected function getDefaultRelease(): string
    {
        $version = VersionHelper::getApplicationReference() ?? VersionHelper::getApplicationVersion();
        return VersionHelper::getApplicationName() . '@' . $version;
    }

    /**
     * The root package names the installation, which nothing else in a report does (`__root__`: its `composer.json`
     * lacks a `name`).
     *
     * @return array<string, string>
     */
    protected function getTags(): array
    {
        return [
            'project' => VersionHelper::getApplicationName(),
            'project_version' => VersionHelper::getApplicationVersion(),
        ];
    }
}
