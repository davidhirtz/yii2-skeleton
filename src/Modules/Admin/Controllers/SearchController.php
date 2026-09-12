<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchResultBuilder;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Never scoped to a tenant: users are not scoped to tenants, so an editor sees every tenant's hits. The results are
 * filtered by {@see \Hirtz\Skeleton\Models\Interfaces\SearchableInterface::getSearchResult()} instead.
 */
class SearchController extends Controller
{
    /**
     * The id the navbar input selects out of the suggest response; the body's inherited `hx-select` would otherwise
     * replace the whole page on every keystroke.
     */
    final public const string LIST_ID = 'search-results-list';

    public int $suggestLimit = 8;

    /**
     * @var int a shorter query would run the `title LIKE` fallback over the whole table on every keystroke
     */
    public int $suggestMinLength = 2;
    public int $pageSize = 20;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'suggest'],
                        'roles' => [Widget::ROLE_AUTHENTICATED],
                    ],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if (!Search::getComponent()->isEnabled()) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    public function actionSuggest(?string $q = null): string
    {
        $q = trim((string)$q);

        if (mb_strlen($q) < $this->suggestMinLength) {
            return '';
        }

        return (string)SearchResultList::make()
            ->attribute('id', self::LIST_ID)
            ->query($q)
            ->results($this->getResults($q, $this->suggestLimit));
    }

    public function actionIndex(?string $q = null): Response|string
    {
        $q = trim((string)$q);

        $pagination = new Pagination([
            'pageSize' => $this->pageSize,
            'totalCount' => $q !== '' ? Search::getComponent()->count($this->createRequest($q, 1)) : 0,
        ]);

        return $this->render('index', [
            'pagination' => $pagination,
            'query' => $q,
            'results' => $this->getResults($q, $pagination->getLimit(), $pagination->getOffset()),
        ]);
    }

    /**
     * @return list<SearchResult>
     */
    protected function getResults(string $q, int $limit, int $offset = 0): array
    {
        if ($q === '') {
            return [];
        }

        /** @var SearchResultBuilder $builder */
        $builder = Yii::createObject(SearchResultBuilder::class, [Search::getComponent()]);

        return $builder->build($this->createRequest($q, $limit, $offset));
    }

    protected function createRequest(string $q, int $limit, int $offset = 0): SearchRequest
    {
        return new SearchRequest(query: $q, limit: $limit, offset: $offset);
    }
}
