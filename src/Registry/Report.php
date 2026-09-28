<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Registry;

use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Log\SentryTarget;
use JsonSerializable;
use Yii;
use yii\base\InvalidConfigException;

/**
 * What an installation says about itself to the version registry: the system page's *Application* tab,
 * serialized. Nothing in it is personal data. A project re-points it in the container to add keys under `extra`.
 */
class Report implements JsonSerializable
{
    final public const int SCHEMA = 1;
    final public const int SENTRY_KEY_LENGTH = 8;

    /**
     * @var string|null the installation's URL, for a console application whose URL manager has no `hostInfo`
     */
    public ?string $url = null;

    /**
     * @var array<string, mixed>
     */
    public array $extra = [];

    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config = []): static
    {
        return Yii::$container->get(static::class, [], $config);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $db = Yii::$app->getDb();

        $report = [
            'schema' => self::SCHEMA,
            'name' => VersionHelper::getApplicationName(),
            'version' => VersionHelper::getApplicationVersion(),
            'reference' => VersionHelper::getApplicationReference(),
            'deployed_at' => VersionHelper::getApplicationUpdatedAt(),
            'hostname' => gethostname() ?: null,
            'base_path' => Yii::$app->getBasePath(),
            'url' => $this->getUrl(),
            'php' => PHP_VERSION,
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'database' => [
                'driver' => $db->getDriverName(),
                'version' => $db->getServerVersion(),
            ],
            'yii' => Yii::getVersion(),
            'extensions' => VersionHelper::getInstalledExtensions(),
            'migration' => $this->getMigration(),
            'mailer' => $this->getMailer(),
            'sentry' => $this->getSentry(),
            'reported_at' => time(),
        ];

        if ($this->extra) {
            $report['extra'] = $this->extra;
        }

        return $report;
    }

    /**
     * A console URL manager without a configured `hostInfo` throws rather than answering `null`; the registry shows
     * hostname and path for an installation without a URL.
     */
    protected function getUrl(): ?string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        try {
            return Yii::$app->getUrlManager()->getHostInfo() ?: null;
        } catch (InvalidConfigException) {
            return null;
        }
    }

    /**
     * @return array{version: string|null, applied_at: int|null, pending: int}
     */
    protected function getMigration(): array
    {
        /** @var MigrationHistory $history */
        $history = Yii::createObject(MigrationHistory::class, [Yii::$app->getDb()]);
        $last = $history->getLastApplied();

        return [
            'version' => $last['version'] ?? null,
            'applied_at' => $last['applyTime'] ?? null,
            'pending' => count($history->getPending()),
        ];
    }

    /**
     * A file transport sends nothing, so it reports no mailer.
     */
    protected function getMailer(): ?string
    {
        $mailer = Yii::$app->getMailer();

        if ($mailer->useFileTransport) {
            return null;
        }

        $dsn = $mailer->getMaskedTransportDsn();
        return $dsn !== null ? mb_substr($dsn, 0, 255) : null;
    }

    /**
     * @return string|null the beginning of the Sentry DSN's public key, `null` without an enabled Sentry target
     */
    protected function getSentry(): ?string
    {
        foreach (Yii::$app->getLog()->targets as $target) {
            if ($target instanceof SentryTarget && $target->getEnabled()) {
                $key = parse_url((string)$target->dsn, PHP_URL_USER);
                return substr(is_string($key) ? $key : '', 0, self::SENTRY_KEY_LENGTH);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
