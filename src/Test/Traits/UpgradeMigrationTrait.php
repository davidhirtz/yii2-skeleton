<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

use Yii;
use yii\db\Migration;

/**
 * Loads the file rather than autoloading the class, and skips when the `davidhirtz/yii2-upgrade` checkout beside this
 * one is absent.
 */
trait UpgradeMigrationTrait
{
    /** The baselines never create these v2 tables, so a test of the upgrade's copy arranges its own source. */
    protected function createLegacyTable(string $table): void
    {
        $db = Yii::$app->getDb();

        if ($db->getSchema()->getTableSchema("{{%$table}}", true) !== null) {
            return;
        }

        $db->createCommand((string)file_get_contents($this->findUpgradeFile("legacy/$table.sql")))->execute();
    }

    /**
     * Guarded, because a test skipped in `setUp()` for the missing checkout still runs its `tearDown()`.
     */
    protected function dropLegacyTable(string $table): void
    {
        $db = Yii::$app->getDb();

        if ($db->getSchema()->getTableSchema("{{%$table}}", true) !== null) {
            $db->createCommand()->dropTable("{{%$table}}")->execute();
        }
    }

    /**
     * Named as a string, never `X::class`, since PHPStan in a standalone checkout does not know the class.
     *
     * @param array<string, mixed> $config
     */
    protected function createUpgradeMigration(string $bundle, string $class, array $config = []): Migration
    {
        $this->requireUpgradeMigration($bundle, $class);
        $migration = new $class($config);

        if (!$migration instanceof Migration) {
            self::fail("$class is not a migration.");
        }

        return $migration;
    }

    protected function requireUpgradeMigration(string $bundle, string $class): void
    {
        if (class_exists($class, false)) {
            return;
        }

        $name = substr((string)strrchr($class, '\\'), 1);

        require_once $this->findUpgradeFile("$bundle/$name.php");
    }

    /**
     * Walked rather than counted, since the trait is read from a bundle, the monorepo and a standalone checkout at
     * different depths.
     */
    private function findUpgradeFile(string $path): string
    {
        for ($directory = __DIR__; $directory !== dirname($directory); $directory = dirname($directory)) {
            $candidate = "$directory/yii2-upgrade/migrations/$path";

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        self::markTestSkipped("davidhirtz/yii2-upgrade is not beside this checkout, so $path cannot be read.");
    }
}
