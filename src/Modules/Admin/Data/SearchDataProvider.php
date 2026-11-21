<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Data;

use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchResultBuilder;
use Override;
use Yii;
use yii\data\BaseDataProvider;

/**
 * Each row wraps its result, since a grid column reads an array or a model, never the readonly DTO.
 */
class SearchDataProvider extends BaseDataProvider
{
    /**
     * `null` finds nothing: no query, or no class the user sees.
     */
    public ?SearchRequest $request = null;

    /**
     * @return list<array{result: SearchResult}>
     */
    #[Override]
    protected function prepareModels(): array
    {
        if (!$this->request) {
            return [];
        }

        $request = $this->request;
        $pagination = $this->getPagination();

        if ($pagination !== false) {
            $pagination->totalCount = $this->getTotalCount();

            if (!$pagination->totalCount) {
                return [];
            }

            $request = $request->withLimit($pagination->getLimit(), $pagination->getOffset());
        }

        /** @var SearchResultBuilder $builder */
        $builder = Yii::createObject(SearchResultBuilder::class, [Search::getComponent()]);

        return array_map(fn (SearchResult $result): array => ['result' => $result], $builder->build($request, false));
    }

    /**
     * @param array<int, mixed> $models
     * @return list<int>
     */
    #[Override]
    protected function prepareKeys($models): array
    {
        return array_keys($models);
    }

    #[Override]
    protected function prepareTotalCount(): int
    {
        return $this->request ? Search::getComponent()->count($this->request) : 0;
    }
}
