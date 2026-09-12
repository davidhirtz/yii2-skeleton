<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;

/**
 * Loads the hits one query per class, never one per hit, and over-fetches because the permission check of
 * {@see SearchableInterface::getSearchResult()} drops hits after the fact.
 */
class SearchResultBuilder
{
    public function __construct(
        private readonly Search $search,
        private readonly int $overFetch = 2,
    ) {
    }

    /**
     * @return list<SearchResult>
     */
    public function build(SearchRequest $request): array
    {
        $limit = max(1, $request->limit * $this->overFetch);
        $offset = $request->offset;
        $results = [];

        while (count($results) < $request->limit) {
            $hits = $this->search->search($request->withLimit($limit, $offset))->hits;

            if (!$hits) {
                break;
            }

            $results = [...$results, ...$this->hydrate($hits)];
            $offset += $limit;
        }

        return array_slice($results, 0, $request->limit);
    }

    /**
     * @param list<SearchHit> $hits
     * @return list<SearchResult>
     */
    protected function hydrate(array $hits): array
    {
        $records = $this->findRecords($hits);
        $results = [];

        foreach ($hits as $hit) {
            $record = $records["$hit->modelClass#$hit->modelId"] ?? null;
            $result = $record?->getSearchResult();

            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * @param list<SearchHit> $hits
     * @return array<string, ActiveRecord&SearchableInterface>
     */
    protected function findRecords(array $hits): array
    {
        $modelIds = [];

        foreach ($hits as $hit) {
            $modelIds[$hit->modelClass][] = $hit->modelId;
        }

        $records = [];

        foreach ($modelIds as $modelClass => $ids) {
            if (!is_subclass_of($modelClass, ActiveRecord::class) || !is_subclass_of($modelClass, SearchableInterface::class)) {
                continue;
            }

            $query = $modelClass::findSearchable();
            $query->andWhere([$query->getTableAlias() . '.[[id]]' => array_unique($ids)]);

            foreach ($query->all() as $record) {
                if ($record instanceof SearchableInterface) {
                    $records["$modelClass#{$record->getPrimaryKey()}"] = $record;
                }
            }
        }

        return $records;
    }
}
