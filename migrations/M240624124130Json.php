<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M240624124130Json extends Migration
{
    use \davidhirtz\yii2\skeleton\db\MigrationTrait;

    public function safeUp(): void
    {
        echo "Updating trail records ... ";

        $query = Trail::find()->select(['id', 'data']);

        $totalCount = 0;
        $updatedCount = 0;

        foreach ($query->each() as $trail) {
            // @phpstan-ignore-next-line
            if (is_string($trail->data)) {
                $trail->updateAttributes(['data' => json_decode($trail->data, true)]);
                $updatedCount++;
            }

            $totalCount++;
        }

        $updatedCount = Yii::$app->getFormatter()->asInteger($updatedCount);
        $totalCount = Yii::$app->getFormatter()->asInteger($totalCount);

        echo "done.\nUpdated $updatedCount / $totalCount rows.\n";
    }

    public function safeDown(): void
    {
    }
}
