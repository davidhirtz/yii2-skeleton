<?php

namespace davidhirtz\yii2\skeleton\models\queries;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * @template T
 * @extends ActiveQuery<T>
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
            'first_name',
            'last_name',
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
            'first_name',
            'last_name',
            'picture',
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

            if (count($keywords) == 1) {
                $keyword = array_pop($keywords);

                if (is_numeric($keyword)) {
                    $this->andWhere("$tableName.[[id]]=:id", ['id' => $keyword]);
                } elseif (str_contains((string)$keyword, '@')) {
                    $this->andWhere("$tableName.[[email]] LIKE :search", ['search' => "%$keyword%"]);
                } else {
                    $this->andWhere("$tableName.[[name]] LIKE :search OR $tableName.[[email]] LIKE :search OR $tableName.[[first_name]] LIKE :search OR $tableName.[[last_name]] LIKE :search", ['search' => "$keyword%"]);
                }
            } else {
                $this->andWhere("$tableName.[[first_name]] LIKE :name OR $tableName.[[last_name]] LIKE :name OR ($tableName.[[first_name]] LIKE :firstname AND $tableName.[[last_name]] LIKE :lastname) OR ($tableName.[[first_name]] LIKE :lastname AND $tableName.[[last_name]] LIKE :firstname)", [
                    'name' => implode(' ', $keywords) . '%',
                    'firstname' => array_pop($keywords) . '%',
                    'lastname' => implode(' ', $keywords) . '%',
                ]);
            }
        }

        return $this;
    }

    /**
     * Overrides default implementation to not use `whereStatus` as this might trigger the draft modus on modules such
     * as `yii2-cms` or `yii2-media`.
     */
    public function enabled(): static
    {
        return $this->andWhere(['>=', User::tableName() . '.status', User::STATUS_ENABLED]);
    }
}
