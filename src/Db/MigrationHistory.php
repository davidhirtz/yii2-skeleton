<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Hirtz\Skeleton\Helpers\NamespaceHelper;
use Yii;
use yii\db\Connection as BaseConnection;
use yii\db\Query;

/**
 * Reads the migration state from outside the console, so the admin can report the last applied migration and warn
 * about pending ones. The namespaces come from `Base\Traits\ApplicationTrait::getMigrationNamespaces()`, which is
 * populated under both SAPIs.
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
     * @return array<string, int> the applied migration class names, mapped to their apply time, newest first.
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
     * Applied migrations whose class cannot be loaded. On a healthy installation this is empty; a v2 database
     * pointed at v3 code returns all of them, because the namespaces were renamed. A project that deleted one
     * of its own migrations without clearing the row shows up here too, which is worth knowing either way.
     *
     * @return list<string>
     */
    public function getUnresolved(): array
    {
        $unresolved = [];

        foreach (array_keys($this->getApplied()) as $version) {
            if (str_contains($version, '\\') && !class_exists($version)) {
                $unresolved[] = $version;
            }
        }

        return $unresolved;
    }

    /**
     * Forgets what it read, for a caller that has just rewritten the table.
     */
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
     * @return list<string> the migration class names found on disk that the database has not applied, oldest first.
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
