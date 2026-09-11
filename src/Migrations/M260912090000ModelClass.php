<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use yii\db\Migration;

/**
 * The migrations that create the tables keep their original column, so this must stay a no-op on a fresh install.
 *
 * @noinspection PhpUnused
 */
class M260912090000ModelClass extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        if ($this->hasColumn(Trail::tableName(), 'model')) {
            $this->dropIndexIfExists('model', Trail::tableName());
            $this->renameColumn(Trail::tableName(), 'model', 'model_class');
            $this->createIndex('model_class', Trail::tableName(), ['model_class', 'model_id']);
            $this->renameTrailDataKey('model', 'model_class');
        }

        if ($this->hasColumn(Translation::tableName(), 'model')) {
            $this->dropIndexIfExists('model', Translation::tableName());
            $this->renameColumn(Translation::tableName(), 'model', 'model_class');

            $this->createIndex(
                'model_class',
                Translation::tableName(),
                ['model_class', 'model_id', 'language', 'attribute'],
                true
            );
        }
    }

    public function safeDown(): void
    {
        if ($this->hasColumn(Translation::tableName(), 'model_class')) {
            $this->dropIndexIfExists('model_class', Translation::tableName());
            $this->renameColumn(Translation::tableName(), 'model_class', 'model');

            $this->createIndex(
                'model',
                Translation::tableName(),
                ['model', 'model_id', 'language', 'attribute'],
                true
            );
        }

        if ($this->hasColumn(Trail::tableName(), 'model_class')) {
            $this->renameTrailDataKey('model_class', 'model');
            $this->dropIndexIfExists('model_class', Trail::tableName());
            $this->renameColumn(Trail::tableName(), 'model_class', 'model');
            $this->createIndex('model', Trail::tableName(), ['model', 'model_id']);
        }
    }

    /**
     * The child trail types store the class name of the record they point at in their JSON data.
     */
    protected function renameTrailDataKey(string $from, string $to): void
    {
        $trail = $this->getQuotedTableName(Trail::tableName());

        $this->execute("
            UPDATE $trail
            SET [[data]] = JSON_REMOVE(JSON_SET([[data]], '$.$to', JSON_EXTRACT([[data]], '$.$from')), '$.$from')
            WHERE JSON_CONTAINS_PATH([[data]], 'one', '$.$from')
        ");
    }
}
