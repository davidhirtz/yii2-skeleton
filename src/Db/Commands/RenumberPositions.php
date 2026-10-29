<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Commands;

use yii\db\Connection;

/**
 * Built from strings, so a migration may run it as well as a model.
 */
class RenumberPositions
{
    /**
     * @param list<string> $partitionBy
     * @param array<string, mixed> $condition
     */
    public function __construct(
        protected Connection $db,
        protected string $table,
        protected array $partitionBy,
        protected array $condition = [],
        protected string $column = 'position',
    ) {
    }

    public function execute(): int
    {
        $params = [];
        $where = $this->condition
            ? 'WHERE ' . $this->db->getQueryBuilder()->buildCondition($this->condition, $params)
            : '';

        $table = $this->db->quoteTableName($this->table);
        $column = $this->db->quoteColumnName($this->column);
        $partitionBy = implode(', ', array_map($this->db->quoteColumnName(...), $this->partitionBy));

        $sql = "UPDATE $table [[t]] INNER JOIN (SELECT [[id]], ROW_NUMBER() OVER (PARTITION BY $partitionBy ORDER BY $column, [[id]]) AS [[new_position]] FROM $table $where) [[r]] ON [[t]].[[id]] = [[r]].[[id]] SET [[t]].$column = [[r]].[[new_position]] WHERE [[t]].$column <> [[r]].[[new_position]]";

        return $this->db->createCommand($sql, $params)->execute();
    }
}
