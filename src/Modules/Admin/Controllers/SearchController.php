<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchResultBuilder;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;
use Override;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Hirtz\Skeleton\Modules\Admin\Module;

/**
 * Never scoped to a tenant, since users are not:
 * {@see \Hirtz\Skeleton\Models\Interfaces\SearchableInterface::getSearchResult()} filters instead.
 *
 * @extends Controller<Module>
 */
class SearchController extends Controller
{
    /**
     * Selected by the navbar input, since the body's inherited `hx-select` would otherwise swap the whole page.
     */
    final public const string LIST_ID = 'search-results-list';

    public int $suggestLimit = 8;

    /**
     * A shorter query would run the `title LIKE` fallback over the whole table on every keystroke.
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
                        'roles' => [User::ROLE_AUTHENTICATED],
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
            ->listbox()
            ->query($q)
            ->results($this->getResults($q, $this->suggestLimit));
    }

    public function actionIndex(?string $q = null): Response|string
    {
        $q = trim((string)$q);

        $request = $q !== '' ? $this->createRequest($q, 1) : null;

        $pagination = new Pagination([
            'pageSize' => $this->pageSize,
            'totalCount' => $request ? Search::getComponent()->count($request) : 0,
        ]);

        return $this->render('index', [
            'pagination' => $pagination,
            'query' => $q,
            'results' => $this->getResults($q, $pagination->getLimit(), $pagination->getOffset(), false),
        ]);
    }

    /**
     * @return list<SearchResult>
     */
    protected function getResults(string $q, int $limit, int $offset = 0, bool $fill = true): array
    {
        $request = $q !== '' ? $this->createRequest($q, $limit, $offset) : null;

        if (!$request) {
            return [];
        }

        /** @var SearchResultBuilder $builder */
        $builder = Yii::createObject(SearchResultBuilder::class, [Search::getComponent()]);

        return $builder->build($request, $fill);
    }

    /**
     * Limited to the classes the user sees, so the pager counts only hits a page can show.
     */
    protected function createRequest(string $q, int $limit, int $offset = 0): ?SearchRequest
    {
        $models = array_values(array_filter(
            Search::getComponent()->getModelClasses(),
            fn (string $class): bool => $class::isSearchResultClassVisible(),
        ));

        return $models ? new SearchRequest(query: $q, models: $models, limit: $limit, offset: $offset) : null;
    }
}
