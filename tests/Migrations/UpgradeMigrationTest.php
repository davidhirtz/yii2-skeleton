<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use ReflectionClass;
use yii\db\Migration;

/**
 * The v2 → v3 migrations live in `davidhirtz/yii2-upgrade` and are only ever loaded by a test here, so this is
 * the one place that proves the file the upgrade ships declares the class the history names it by.
 */
class UpgradeMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;

    public function testASkeletonUpgradeMigrationLoadsFromTheUpgradeCheckout(): void
    {
        $class = $this->getMigrationClass();
        $this->requireUpgradeMigration('yii2-skeleton', $class);

        if (!class_exists($class, false)) {
            self::fail("$class was not declared by the file the upgrade ships.");
        }

        self::assertTrue((new ReflectionClass($class))->isSubclassOf(Migration::class));
    }

    /**
     * Answered by a method so PHPStan sees a plain string in both checkouts: the monorepo scans the upgrade
     * checkout and knows the class, a standalone one does not, and a literal would be typed differently in each.
     */
    private function getMigrationClass(): string
    {
        return 'Hirtz\Skeleton\Migrations\M260914190000ManagerRole';
    }
}
