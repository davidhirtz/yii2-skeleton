<?php
/**
 * This view is used by console/controllers/MigrateController.php.
 *
 * @var string $className the new migration class name without namespace
 * @var string $namespace the new migration class namespace
 */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use davidhirtz\yii2\skeleton\db\MigrationTrait;
use yii\db\Migration;

/**
* @noinspection PhpUnused
*/
class <?= $className ?> extends Migration
{
    use MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp(): void
    {

    }

    /**
     * @inheritDoc
     */
    public function safeDown(): void
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}