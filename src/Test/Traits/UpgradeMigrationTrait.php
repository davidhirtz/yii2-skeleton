<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

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
    protected function requireUpgradeMigration(string $bundle, string $class): void
    {
        if (class_exists($class, false)) {
            return;
        }

        $name = substr((string)strrchr($class, '\\'), 1);
        $file = null;

        // Walked rather than counted: this trait is read from a bundle's tests, from the monorepo and from a
        // standalone checkout, and the number of levels up is different in each.
        for ($directory = __DIR__; $directory !== dirname($directory); $directory = dirname($directory)) {
            $candidate = "$directory/yii2-upgrade/migrations/$bundle/$name.php";

            if (is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }

        if ($file === null) {
            self::markTestSkipped("davidhirtz/yii2-upgrade is not beside this checkout, so $name cannot be loaded.");
        }

        require_once $file;
    }
}
