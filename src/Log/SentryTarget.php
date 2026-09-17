<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log;

use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Sentry\ClientBuilder;
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
 * Reports to Sentry beside the file log rather than instead of it. `Base\Traits\ApplicationTrait` adds one when
 * `params.sentryDsn` is set, so an installation without the parameter carries no Sentry client at all.
 */
class SentryTarget extends Target
{
    public ?string $dsn = null;

    /**
     * Both are what a report is grouped and filtered by, and both are resolved in the same order: this property,
     * then Sentry's own `SENTRY_ENVIRONMENT` / `SENTRY_RELEASE` variable, then a default of ours.
     */
    public ?string $environment = null;
    public ?string $release = null;

    /**
     * @see https://docs.sentry.io/platforms/php/configuration/options/
     * @var array<string, mixed>
     */
    public array $clientOptions = [];

    /**
     * Nothing, deliberately: `Target::collect()` appends the context dump as a message of its own, and the
     * masking that makes it safe for a file on this server is not what should reach an external service. The
     * request integration carries the URL and the method instead.
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

        if ($user = $this->getUser()) {
            $scope->setUser($user);
        }

        if ($text instanceof Throwable) {
            $hub->captureException($text);
            return;
        }

        $hub->captureMessage(is_string($text) ? $text : VarDumper::export($text), $this->getSeverity($level));
    }

    /**
     * The id alone: an account's name and address are the user's, and Sentry is not this server. The identity is
     * read without renewing it, so a report never opens a session of its own.
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
     * The hub is Sentry's own process-wide singleton, so it is set rather than read: the target is configured from
     * this application's parameters and must not report through a client another one left behind. `\Sentry\`'s own
     * helpers reach the same client from anywhere afterwards.
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
            // Last, and merged rather than replaced: a project adding a tag of its own must not drop this one.
            'tags' => [...$this->getTags(), ...$this->clientOptions['tags'] ?? []],
        ];
    }

    /**
     * Sentry's own `package@version` convention, so the Releases view reads as the project rather than as a bare
     * commit. The version is the deployed commit where there is one, abbreviated as everywhere else here: an
     * installation reaching this default has no pipeline registering releases, and therefore no commits to
     * associate a full reference with.
     */
    protected function getDefaultRelease(): string
    {
        $version = VersionHelper::getApplicationReference() ?? VersionHelper::getApplicationVersion();
        return VersionHelper::getApplicationName() . '@' . $version;
    }

    /**
     * `Client` merges these into every event. **Which installation** is the one thing a report cannot be read
     * without and nothing else carries: the frames point into the same twelve bundle repositories whichever
     * deployment raised them, and `server_name` is the host rather than the project. Composer's root package is
     * the answer — a project whose `composer.json` declares no `name` reports `__root__`, which is the sign to
     * give it one.
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
