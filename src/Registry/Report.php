<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Registry;

use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Helpers\VersionHelper;
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
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
