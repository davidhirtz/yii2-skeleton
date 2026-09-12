<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Search;

class MysqlDriver implements SearchDriverInterface
{
    public function index(SearchDocument ...$documents): void
    {
        if (!$documents) {
            return;
        }

        $updatedAt = (string)new DateTime();
        $modelIds = [];
        $rows = [];

        foreach ($documents as $document) {
            $modelIds[$document->modelClass][] = $document->modelId;

            $rows[] = [
                'model_class' => $document->modelClass,
                'model_id' => $document->modelId,
                'language' => $document->language,
                'tenant_id' => $document->tenantId,
                'status' => $document->status,
                'weight' => $document->weight,
                'title' => $document->title,
                'content' => $document->content,
                'updated_at' => $updatedAt,
            ];
        }

        foreach ($modelIds as $modelClass => $ids) {
            Search::deleteAll([
                'model_class' => $modelClass,
                'model_id' => array_values(array_unique($ids)),
            ]);
        }

        Search::batchInsert($rows);
    }

    public function delete(string $modelClass, int $modelId): void
    {
        Search::deleteAll([
            'model_class' => $modelClass,
            'model_id' => $modelId,
        ]);
    }

    public function search(SearchRequest $request): SearchResultSet
    {
        $rows = $this->createQuery($request)
            ->orderByScore()
            ->limit($request->limit)
            ->offset($request->offset)
            ->asArray()
            ->all();

        $hits = [];

        foreach ($rows as $row) {
            /** @var class-string $modelClass */
            $modelClass = $row['model_class'];
            $hits[] = new SearchHit($modelClass, (int)$row['model_id'], (float)$row['score']);
        }

        return new SearchResultSet($hits);
    }

    public function count(SearchRequest $request): int
    {
        return (int)$this->createQuery($request)->count();
    }

    public function clear(?string $modelClass = null): void
    {
        Search::deleteAll($modelClass !== null ? ['model_class' => $modelClass] : null);
    }

    /**
     * @return SearchQuery<Search>
     */
    protected function createQuery(SearchRequest $request): SearchQuery
    {
        $query = Search::find()
            ->matching($request->query)
            ->groupByModel();

        if ($request->languages) {
            $query->languages($request->languages);
        }

        if ($request->models) {
            $query->models($request->models);
        }

        if ($request->tenantId !== null) {
            $query->tenant($request->tenantId);
        }

        if ($request->status !== null) {
            $query->whereStatus($request->status);
        }

        return $query;
    }
}
