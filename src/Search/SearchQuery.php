<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\Search;
use Override;
use yii\db\Expression;
use yii\db\Query;

/**
 * The MySQL driver's query builder. The admin never calls {@see static::tenant()} or `enabled()`: those are the
 * frontend presets, where a site must only ever surface its own tenant's enabled records.
 *
 * @template T of Search
 * @extends ActiveQuery<T>
 */
class SearchQuery extends ActiveQuery
{
    public const string SCORE = 'score';

    /**
     * @var float multiplied into the title match, so a hit in the name outranks one in the body
     */
    public float $titleBoost = 3.0;

    private ?string $scoreExpression = null;
    private bool $isGroupedByModel = false;

    /**
     * Falls back to a `title LIKE` when no token survives the sanitizer, which is the only path a two-character
     * query has.
     */
    public function matching(?string $search): static
    {
        $search = trim((string)$search);
        $tokens = SearchText::tokenize($search);

        if ($tokens) {
            return $this->matchingTokens($tokens);
        }

        return $search !== '' ? $this->matchingTitle($search) : $this->emulateExecution();
    }

    /**
     * @param list<string> $tokens
     */
    protected function matchingTokens(array $tokens): static
    {
        $alias = $this->getTableAlias();
        $against = SearchText::toBooleanQuery($tokens);

        $title = "MATCH($alias.[[title]]) AGAINST(:searchTitle IN BOOLEAN MODE)";
        $content = "MATCH($alias.[[title]], $alias.[[content]]) AGAINST(:searchContent IN BOOLEAN MODE)";

        $this->scoreExpression = "(($title) * $this->titleBoost + ($content)) * $alias.[[weight]]";

        $this->andWhere(new Expression("MATCH($alias.[[title]], $alias.[[content]]) AGAINST(:searchMatch IN BOOLEAN MODE)"));

        return $this->addParams([
            ':searchTitle' => $against,
            ':searchContent' => $against,
            ':searchMatch' => $against,
        ]);
    }

    protected function matchingTitle(string $search): static
    {
        $alias = $this->getTableAlias();
        $this->scoreExpression = "$alias.[[weight]]";

        return $this->andWhere(['like', "$alias.[[title]]", $this->sanitizeSearchString($search)]);
    }

    /**
     * @param list<string>|string $languages
     */
    public function languages(array|string $languages): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[language]]' => $languages]);
    }

    /**
     * @param list<class-string>|class-string $models
     */
    public function models(array|string $models): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[model_class]]' => $models]);
    }

    public function tenant(?int $tenantId): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[tenant_id]]' => $tenantId]);
    }

    /**
     * Collapses the per-language rows of a record into one hit, so an editor working in English still finds
     * "Kontakt". Only the grouped columns are selected, which is also all the hydration needs.
     */
    public function groupByModel(): static
    {
        $alias = $this->getTableAlias();
        $this->isGroupedByModel = true;

        return $this->groupBy(["$alias.[[model_class]]", "$alias.[[model_id]]"]);
    }

    public function orderByScore(): static
    {
        return $this->orderBy([
            self::SCORE => SORT_DESC,
            $this->getTableAlias() . '.[[model_id]]' => SORT_ASC,
        ]);
    }

    /**
     * The select is applied here rather than in {@see static::matching()}, so the builders can be called in any
     * order and a repeated `prepare()` stays idempotent.
     */
    #[Override]
    public function prepare($builder): Query
    {
        $this->applySelect();
        return parent::prepare($builder);
    }

    protected function applySelect(): void
    {
        $alias = $this->getTableAlias();
        $score = $this->scoreExpression ?? "$alias.[[weight]]";

        if ($this->isGroupedByModel) {
            $this->select = [
                'model_class' => "$alias.[[model_class]]",
                'model_id' => "$alias.[[model_id]]",
                self::SCORE => new Expression("MAX($score)"),
            ];

            return;
        }

        if (empty($this->select)) {
            $this->selectAllColumns();
        }

        $this->addSelect([self::SCORE => new Expression($score)]);
    }
}
