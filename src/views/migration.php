<?php
declare(strict_types=1);

/**
 * @var string $className the new migration class name without namespace
 * @var string $namespace the new migration class namespace
 */

echo "<?php\n\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace $namespace;\n";
}
?>

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
final class <?= $className ?> extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
    }

    public function safeDown(): void
    {
    }
}
