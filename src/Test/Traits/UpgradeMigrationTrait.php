<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

use Yii;

/**
 * Loads a v2 → v3 migration from the `davidhirtz/yii2-upgrade` checkout beside this one.
 *
 * `v3-migration-squash.md` §4 moved those migrations out of the bundles: a bundle that keeps them carries v2
 * trivia in every release forever, and a new project should install one migration rather than a dozen. Their
 * **tests stay here**, because the thing that runs them is here — the application, the fixtures, a database
 * with the v3 schema — and the upgrade repository has none of it.
 *
 * So the test loads the file rather than autoloading the class. The import copies them verbatim, namespace
 * included, so the class name is the one it always was.
 *
 * It skips when the checkout is not there, the way the upgrade repository's own tests skip without this one.
 */
trait UpgradeMigrationTrait
{
    /**
     * Creates a v2 table the upgrade copies away, so a test can replay that copy.
     *
     * The baselines do not create these -- a fresh install has nothing to copy, and a baseline must not
     * recreate a legacy table -- so the test that exercises the copy arranges its own source, the way it
     * already arranges the count column beside it.
     */
    protected function createLegacyTable(string $table): void
    {
        $db = Yii::$app->getDb();

        if ($db->getSchema()->getTableSchema("{{%$table}}", true) !== null) {
            return;
        }

        $db->createCommand((string)file_get_contents($this->findUpgradeFile("legacy/$table.sql")))->execute();
    }

    protected function dropLegacyTable(string $table): void
    {
        Yii::$app->getDb()->createCommand()->dropTable("{{%$table}}")->execute();
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
     * Walked rather than counted: this trait is read from a bundle's tests, from the monorepo and from a
     * standalone checkout, and the number of levels up is different in each.
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
