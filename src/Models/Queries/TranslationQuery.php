<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Queries;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\Translation;

/**
 * @template T of Translation
 * @extends ActiveQuery<T>
 */
class TranslationQuery extends ActiveQuery
{
    /**
     * @param class-string $class
     * @param int|list<int> $ids
     */
    public function whereModel(string $class, int|array $ids): static
    {
        $alias = $this->getTableAlias();

        return $this->andWhere([
            "$alias.[[model]]" => $class,
            "$alias.[[model_id]]" => $ids,
        ]);
    }

    /**
     * @param list<string>|string $languages
     */
    public function whereLanguage(array|string $languages): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[language]]' => $languages]);
    }

    /**
     * @param list<string>|string $attributes
     */
    public function whereAttribute(array|string $attributes): static
    {
        return $this->andWhere([$this->getTableAlias() . '.[[attribute]]' => $attributes]);
    }
}
