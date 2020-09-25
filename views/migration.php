<?php
/**
 * This view is used by console/controllers/MigrateController.php.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use davidhirtz\yii2\skeleton\db\MigrationTrait;
use yii\db\Migration;

/**
* Class <?= $className . "\n" ?>
* @package <?= $namespace . "\n" ?>
* @noinspection PhpUnused
*/
class <?= $className ?> extends Migration
{
    use MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp()
    {

    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}