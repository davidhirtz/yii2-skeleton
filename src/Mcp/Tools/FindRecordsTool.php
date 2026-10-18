<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchHit;
use Hirtz\Skeleton\Search\SearchRequest;
use Override;
use yii\db\Expression;

class FindRecordsTool extends ResourceTool
{
    public int $maxLimit = 100;

    /**
     * @var int the search hits a text query narrows the records to before filter, order and offset apply
     */
    public int $maxSearchHits = 1000;

    #[Override]
    public function getName(): string
    {
        return 'find_records';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Finds records of a resource. "filter" matches columns by equality (custom attributes are not '
            . 'columns), "query" searches the text of searchable resources. Returns the total and a summary per record.';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'resource' => $this->getResourceProperty(),
                'filter' => [
                    'type' => 'object',
                    'description' => 'Column => value, a list of values matches any of them, e.g. {"status": 1, "parent_id": null}.',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Text to search for, ordered by relevance unless "order" is given.',
                ],
                'order' => [
                    'type' => 'string',
                    'description' => 'A column, prefixed with "-" for descending, e.g. "-updated_at".',
                ],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => $this->maxLimit, 'default' => 20],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
            'required' => ['resource'],
            'additionalProperties' => false,
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    protected function run(array $arguments): array
    {
        $resource = $this->findResource($arguments);
        $modelClass = $resource->getModelClass();
        $tableName = $modelClass::tableName();
        $columns = $modelClass::getTableSchema()->getColumnNames();

        $query = $resource->find();

        /** @var array<string, mixed> $filter */
        $filter = $arguments['filter'] ?? [];

        foreach ($filter as $column => $value) {
            if (!in_array($column, $columns, true)) {
                throw new ToolException("Unknown column \"$column\", filter takes columns only.", ['columns' => $columns]);
            }

            if (!is_scalar($value) && $value !== null && !(is_array($value) && array_is_list($value))) {
                throw new ToolException("The filter value of \"$column\" must be a scalar, null or a list.");
            }

            $query->andWhere(["$tableName.[[$column]]" => $value]);
        }

        $text = trim((string)($arguments['query'] ?? ''));

        if ($text !== '') {
            $ids = $this->search($modelClass, $text);
            $query->andWhere(["$tableName.[[id]]" => $ids ?: [0]]);

            if ($ids) {
                $query->orderBy(new Expression("FIELD($tableName.[[id]], " . implode(',', $ids) . ')'));
            }
        }

        if (is_string($arguments['order'] ?? null)) {
            $column = ltrim($arguments['order'], '-');

            if (!in_array($column, $columns, true)) {
                throw new ToolException("Unknown column \"$column\" to order by.", ['columns' => $columns]);
            }

            $query->orderBy(["$tableName.[[$column]]" => str_starts_with($arguments['order'], '-') ? SORT_DESC : SORT_ASC]);
        }

        $limit = min($this->maxLimit, max(1, (int)($arguments['limit'] ?? 20)));
        $offset = max(0, (int)($arguments['offset'] ?? 0));

        $total = (int)(clone $query)->orderBy(null)->count();
        $records = [];

        foreach ($query->limit($limit)->offset($offset)->all() as $record) {
            if ($record instanceof AdminModelInterface && $this->can($record)) {
                $records[] = $this->getRecordSummary($resource, $record);
            }
        }

        return [
            'total' => $total,
            'offset' => $offset,
            'records' => $records,
        ];
    }

    /**
     * @param class-string $modelClass
     * @return list<int>
     */
    protected function search(string $modelClass, string $text): array
    {
        $search = Search::getComponent();

        if (!is_subclass_of($modelClass, SearchableInterface::class) || !$search->isEnabled()) {
            throw new ToolException('This resource cannot be searched by text, use "filter".');
        }

        $result = $search->search(new SearchRequest(
            query: $text,
            models: [$search->getRegisteredClass($modelClass)],
            limit: $this->maxSearchHits,
        ));

        return array_values(array_unique(array_map(fn (SearchHit $hit): int => $hit->modelId, $result->hits)));
    }
}
