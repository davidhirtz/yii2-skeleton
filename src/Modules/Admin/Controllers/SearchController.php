<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
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
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Hirtz\Skeleton\Modules\Admin\Module;

/**
 * Scoped to a tenant only by the page's `tenant` filter, since users belong to none:
 * {@see \Hirtz\Skeleton\Models\Interfaces\SearchableInterface::getSearchResult()} filters by permission instead.
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

    public function actionIndex(?string $q = null, ?int $tenant = null, ?string $model = null): Response|string
    {
        $q = trim((string)$q);
        $visible = $this->getVisibleModelClasses();
        $models = isset($visible[(string)$model]) ? [$visible[$model]] : array_values($visible);

        $request = $q !== '' ? $this->createRequest($q, 1, tenantId: $tenant, models: $models) : null;

        $pagination = new Pagination([
            'pageSize' => $this->pageSize,
            'totalCount' => $request ? Search::getComponent()->count($request) : 0,
        ]);

        $labels = array_map(fn (string $class): string => Yii::createObject($class)->getAdminType(), $visible);
        uasort($labels, strnatcasecmp(...));

        return $this->render('index', [
            'models' => $labels,
            'pagination' => $pagination,
            'query' => $q,
            'results' => $this->getResults($q, $pagination->getLimit(), $pagination->getOffset(), false, $tenant, $models),
        ]);
    }

    /**
     * @param list<class-string<SearchableInterface>>|null $models
     * @return list<SearchResult>
     */
    protected function getResults(
        string $q,
        int $limit,
        int $offset = 0,
        bool $fill = true,
        ?int $tenantId = null,
        ?array $models = null,
    ): array {
        $request = $q !== '' ? $this->createRequest($q, $limit, $offset, $tenantId, $models) : null;

        if (!$request) {
            return [];
        }

        /** @var SearchResultBuilder $builder */
        $builder = Yii::createObject(SearchResultBuilder::class, [Search::getComponent()]);

        return $builder->build($request, $fill);
    }

    /**
     * Limited to the classes the user sees, so the pager counts only hits a page can show.
     *
     * @param list<class-string<SearchableInterface>>|null $models
     */
    protected function createRequest(
        string $q,
        int $limit,
        int $offset = 0,
        ?int $tenantId = null,
        ?array $models = null,
    ): ?SearchRequest {
        $models ??= array_values($this->getVisibleModelClasses());

        return $models ? new SearchRequest(query: $q, models: $models, tenantId: $tenantId, limit: $limit, offset: $offset) : null;
    }

    /**
     * Keyed by the short class name, so the `model` filter never takes a class name from the URL.
     *
     * @return array<string, class-string<SearchableInterface>>
     */
    protected function getVisibleModelClasses(): array
    {
        $models = [];

        foreach (Search::getComponent()->getModelClasses() as $class) {
            $key = Inflector::camel2id(StringHelper::basename($class));

            while (isset($models[$key])) {
                $key .= '-' . count($models);
            }

            $models[$key] = $class;
        }

        return array_filter($models, fn (string $class): bool => $class::isSearchResultClassVisible());
    }
}
