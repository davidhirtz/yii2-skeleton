<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Commands;

use yii\db\Connection;

/**
 * Renumbers the rows of each parent `1..n` in their current order, closing the gaps a delete or a move leaves, so a
 * row's position is its rank and the parent's count is the total. One statement, idempotent, and built from strings,
 * so a migration may run it as well as a model.
 */
class RenumberPositions
{
    /**
     * @param list<string> $partitionBy the columns naming the parent, e.g. `['entry_id']`
     * @param array<string, mixed> $condition narrows the rows renumbered, typically to one parent
     */
    public function __construct(
        protected Connection $db,
        protected string $table,
        protected array $partitionBy,
        protected array $condition = [],
        protected string $column = 'position',
    ) {
    }

    /**
     * @return int the number of rows whose position changed
     */
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
