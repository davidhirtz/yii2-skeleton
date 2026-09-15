<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Fixtures;

use Override;
use ReflectionClass;
use Yii;
use yii\db\Connection;
use yii\di\Instance;

/**
 * The auto-increment counter is never reset: `ALTER TABLE` is DDL and would commit the test transaction, so a record
 * a test creates gets whatever id the counter has reached, never a fixed number.
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
     * The data file lives in `Data/`, the StudlyCase directory of v3, where Yii looks in `data/` — which only a
     * case-insensitive filesystem resolves — and loads nothing at all when it finds no file.
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

    #[Override]
    protected function resetTable(): void
    {
        $this->getDb()->createCommand()->delete($this->getTableSchema()->fullName)->execute();
    }
}
