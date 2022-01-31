<?php

namespace davidhirtz\yii2\skeleton\models\queries;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * Class UserQuery.
 * @package davidhirtz\yii2\skeleton\models\queries
 *
 * @method User[] all($db = null)
 * @method User one($db = null)
 */
class UserQuery extends ActiveQuery
{
    /**
     * @return $this
     */
    public function selectIdentityAttributes()
    {
        return $this->addSelect([
            'id',
            'status',
            'name',
            'email',
            'password',
            'password_salt',
            'first_name',
            'last_name',
            'picture',
            'language',
            'timezone',
            'email_confirmation_code',
            'password_reset_code',
            'google_2fa_secret',
            'is_owner',
            'login_count',
            'last_login',
            'updated_at'
        ]);
    }

    /**
     * @return $this
     */
    public function nameAttributesOnly()
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

    /**
     * @return $this
     */
    public function selectListAttributes()
    {
        return $this->select($this->prefixColumns([
            'id',
            'status',
            'name',
            'email',
            'first_name',
            'last_name',
            'picture',
            'email_confirmation_code',
            'is_owner',
            'last_login',
            'created_at'
        ]));
    }

    /**
     * @param string $search
     * @return $this
     */
    public function matching($search)
    {
        if ($keywords = $this->splitSearchString($search)) {
            $tableName = User::tableName();

            if (count($keywords) == 1) {
                $keyword = array_pop($keywords);

                if (is_numeric($keyword)) {
                    $this->andWhere("{$tableName}.[[id]]=:id", ['id' => $keyword]);
                } elseif (strpos($keyword, '@') !== false) {
                    $this->andWhere("{$tableName}.[[email]] LIKE :search", ['search' => "%{$keyword}%"]);
                } else {
                    $this->andWhere("{$tableName}.[[name]] LIKE :search OR {$tableName}.[[email]] LIKE :search OR {$tableName}.[[first_name]] LIKE :search OR {$tableName}.[[last_name]] LIKE :search", ['search' => "{$keyword}%"]);
                }
            } else {
                $this->andWhere("{$tableName}.[[first_name]] LIKE :name OR {$tableName}.[[last_name]] LIKE :name OR ({$tableName}.[[first_name]] LIKE :firstname AND {$tableName}.[[last_name]] LIKE :lastname) OR ({$tableName}.[[first_name]] LIKE :lastname AND {$tableName}.[[last_name]] LIKE :firstname)", [
                    'name' => implode(' ', $keywords) . '%',
                    'firstname' => array_pop($keywords) . '%',
                    'lastname' => implode(' ', $keywords) . '%',
                ]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function orderByName()
    {
        return $this->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Overrides default implementation to not use `whereStatus` as this might trigger the draft modus on modules such
     * as `yii2-cms` or `yii2-media`.
     * @return $this
     */
    public function enabled()
    {
        return $this->andWhere(['>=', User::tableName() . '.status', User::STATUS_ENABLED]);
    }
}