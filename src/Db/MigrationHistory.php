<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Hirtz\Skeleton\Helpers\NamespaceHelper;
use Throwable;
use Yii;
use yii\db\Connection as BaseConnection;
use yii\db\Query;

/**
 * The namespaces come from `Base\Traits\ApplicationTrait::getMigrationNamespaces()`, which is populated under both
 * SAPIs.
 */
class MigrationHistory
{
    public const string BASE_MIGRATION = 'm000000_000000_base';

    public string $tableName = '{{%migration}}';

    /**
     * @var array<string, int>|null
     */
    private ?array $applied = null;

    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * Newest first.
     *
     * @return array<string, int>
     */
    public function getApplied(): array
    {
        if ($this->applied === null) {
            $this->applied = [];

            // Refreshed rather than read from the schema cache, which outlives the process: a database
            // recreated behind a warm cache passes the null check and then fails the SELECT with a 1146,
            // which is exactly the state an interrupted restore leaves and therefore the moment this is
            // asked. The result is memoised below, so it costs one query per instance.
            if ($this->db->getSchema()->getTableSchema($this->tableName, true) !== null) {
                $rows = (new Query())
                    ->select(['version', 'apply_time'])
                    ->from($this->tableName)
                    ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC])
                    ->all($this->db);

                foreach ($rows as $row) {
                    if ($row['version'] !== self::BASE_MIGRATION) {
                        $this->applied[trim((string)$row['version'], '\\')] = (int)$row['apply_time'];
                    }
                }
            }
        }

        return $this->applied;
    }

    /**
     * `class_exists()` throws for a file that cannot be declared (a project migration using a trait v3 removed), so
     * such a version is reported rather than raised.
     *
     * @return list<string>
     */
    public function getUnresolved(): array
    {
        $unresolved = [];

        foreach (array_keys($this->getApplied()) as $version) {
            if (!str_contains($version, '\\')) {
                continue;
            }

            try {
                $exists = class_exists($version);
            } catch (Throwable) {
                $exists = false;
            }

            $exists || $unresolved[] = $version;
        }

        return $unresolved;
    }

    public function refresh(): void
    {
        $this->applied = null;
    }

    /**
     * @return array{version: string, applyTime: int}|null
     */
    public function getLastApplied(): ?array
    {
        $applied = $this->getApplied();

        if (!$applied) {
            return null;
        }

        $version = array_key_first($applied);

        return [
            'version' => $version,
            'applyTime' => $applied[$version],
        ];
    }

    /**
     * Oldest first.
     *
     * @return list<string>
     */
    public function getPending(): array
    {
        $applied = $this->getApplied();
        $pending = [];

        foreach (Yii::$app->getMigrationNamespaces() as $namespace) {
            $path = NamespaceHelper::getPath($namespace);

            if ($path === null || !is_dir($path)) {
                continue;
            }

            foreach ((array)scandir($path) as $file) {
                if (!is_string($file) || !preg_match('/^(m(\d{6}_?\d{6})\D.*?)\.php$/is', $file, $matches)) {
                    continue;
                }

                $class = $namespace . '\\' . $matches[1];

                if (!isset($applied[$class])) {
                    $pending[str_replace('_', '', $matches[2]) . '\\' . $class] = $class;
                }
            }
        }

        ksort($pending);
        return array_values($pending);
    }
}
