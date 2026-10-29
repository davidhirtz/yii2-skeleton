<?php
declare(strict_types=1);

/**
 * @var string $className
 * @var string $namespace
 */

echo "<?php\n\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace $namespace;\n";
}
?>

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
final class <?= $className ?> extends Migration
{
    use MigrationTrait;

    #[Override]
    public function safeUp(): void
    {
    }

    #[Override]
    public function safeDown(): void
    {
    }
}
