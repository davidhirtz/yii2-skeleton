<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Fixtures;

use Override;

/**
 * The auto-increment counter is never reset: `ALTER TABLE` is DDL and would commit the test transaction, so a record
 * a test creates gets whatever id the counter has reached, never a fixed number.
 */
abstract class ActiveFixture extends \yii\test\ActiveFixture
{
    #[Override]
    public function load(): void
    {
        $this->data = [];
        $table = $this->getTableSchema();

        foreach ($this->getData() as $alias => $row) {
            $primaryKeys = $this->db->getSchema()->insert($table->fullName, $row) ?: [];
            $this->data[$alias] = [...$row, ...$primaryKeys];
        }
    }

    #[Override]
    protected function resetTable(): void
    {
        $this->db->createCommand()->delete($this->getTableSchema()->fullName)->execute();
    }
}
