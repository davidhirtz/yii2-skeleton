<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\Search;
use Override;
use yii\db\Expression;
use yii\db\Query;

/**
 * The admin never calls {@see static::tenant()} or `enabled()`, the frontend presets.
 *
 * @template T of Search
 * @extends ActiveQuery<T>
 */
class SearchQuery extends ActiveQuery
{
    public const string SCORE = 'score';

    public float $titleBoost = 3.0;

    private ?string $scoreExpression = null;
    private bool $isGroupedByModel = false;

    /**
     * Falls back to a `title LIKE` for a query without a single word character, such as `@` or `...`.
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
     * Collapses a record's per-language rows into one hit, so an editor working in English still finds "Kontakt".
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
            $this->getTableAlias() . '.[[model_class]]' => SORT_ASC,
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
