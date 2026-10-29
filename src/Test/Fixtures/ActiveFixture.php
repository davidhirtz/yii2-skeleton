<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Fixtures;

use Override;
use ReflectionClass;
use Yii;
use yii\db\Connection;
use yii\di\Instance;

/**
 * The auto-increment counter is never reset, since `ALTER TABLE` would commit the test transaction.
 */
abstract class ActiveFixture extends \yii\test\ActiveFixture
{
    /**
     * `DbFixture::init()` resolves the component, but the property keeps the union the parent declares.
     */
    public function getDb(): Connection
    {
        return Instance::ensure($this->db, Connection::class);
    }

    #[Override]
    public function load(): void
    {
        $this->data = [];
        $table = $this->getTableSchema();

        foreach ($this->getData() as $alias => $row) {
            $primaryKeys = $this->getDb()->getSchema()->insert($table->fullName, $row) ?: [];
            $this->data[$alias] = [...$row, ...$primaryKeys];
        }
    }

    /**
     * In `Data/`, the StudlyCase directory, where Yii looks in `data/` and silently loads nothing without a file.
     *
     * @return array<string, array<string, mixed>>
     */
    #[Override]
    protected function getData(): array
    {
        if ($this->dataFile !== null) {
            return parent::getData();
        }

        $fileName = (new ReflectionClass($this))->getFileName() ?: '';
        $directory = $this->dataDirectory ?: dirname($fileName) . '/Data';
        $dataFile = Yii::getAlias($directory) . '/' . $this->getTableSchema()->fullName . '.php';

        return $this->loadData($dataFile);
    }

    /**
     * Removes only the loaded rows, since a test that lost its transaction to DDL would commit deleting rows it never
     * wrote (monorepo #142); without loaded rows it still clears the table.
     */
    #[Override]
    protected function resetTable(): void
    {
        $this->getDb()->createCommand()
            ->delete($this->getTableSchema()->fullName, $this->getLoadedRowsCondition())
            ->execute();
    }

    /**
     * @return array<int|string, mixed>|string
     */
    private function getLoadedRowsCondition(): array|string
    {
        $primaryKey = $this->getTableSchema()->primaryKey;

        if (!$this->data || !$primaryKey) {
            return '';
        }

        $condition = ['or'];

        foreach ($this->data as $row) {
            $values = array_intersect_key((array)$row, array_flip($primaryKey));

            if (count($values) !== count($primaryKey)) {
                return '';
            }

            $condition[] = $values;
        }

        return $condition;
    }
}
