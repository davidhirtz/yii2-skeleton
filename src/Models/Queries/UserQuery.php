<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Queries;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\User;

/**
 * @template T of User
 * @template-extends ActiveQuery<T>
 */
class UserQuery extends ActiveQuery
{
    public function andWhereEmail(string $email): static
    {
        return $this->whereLower([User::tableName() . '.[[email]]' => $email]);
    }

    public function andWhereName(string $name): static
    {
        return $this->whereLower([User::tableName() . '.[[name]]' => $name]);
    }

    public function nameAttributesOnly(): static
    {
        return $this->select($this->prefixColumns([
            'id',
            'status',
            'name',
            'is_owner',
        ]));
    }

    public function selectListAttributes(): static
    {
        return $this->select($this->prefixColumns([
            'id',
            'status',
            'name',
            'email',
            'verification_token',
            'is_owner',
            'last_login',
            'created_at'
        ]));
    }

    public function matching(?string $search): static
    {
        if ($keywords = $this->splitSearchString($search)) {
            $tableName = User::tableName();

            if (count($keywords) === 1) {
                $keyword = reset($keywords);

                if (is_numeric($keyword)) {
                    return $this->andWhere("$tableName.[[id]]=:id", ['id' => $keyword]);
                }

                if (str_contains((string)$keyword, '@')) {
                    return $this->andWhere("$tableName.[[email]] LIKE :search", [
                        'search' => "%$keyword%",
                    ]);
                }
            }

            $this->andWhere("$tableName.[[name]] LIKE :search OR $tableName.[[email]] LIKE :search", [
                'search' => '%' . implode(' ', $keywords) . '%',
            ]);
        }

        return $this;
    }

    /**
     * Overrides default implementation to not use `whereStatus` as this might trigger the draft modus on modules such
     * as `yii2-cms` or `yii2-media`.
     */
    #[\Override]
    public function enabled(): static
    {
        return $this->andWhere(['>=', User::tableName() . '.status', User::STATUS_ENABLED]);
    }
}
