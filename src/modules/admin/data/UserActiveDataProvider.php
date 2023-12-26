<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\data\ActiveDataProvider;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * @property UserQuery|null $query
 * @method User[] getModels()
 */
class UserActiveDataProvider extends ActiveDataProvider
{
    public ?string $searchString = null;

    public function init(): void
    {
        $this->query ??= User::find();
        $this->initQuery();

        parent::init();
    }

    protected function initQuery(): void
    {
        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    public function setSort($value): void
    {
        if (is_array($value) && !isset($value['defaultOrder'])) {
            $value['defaultOrder'] = ['last_login' => SORT_DESC];
        }

        parent::setSort($value);
    }

    public function setPagination($value): void
    {
        if (is_array($value) && !isset($value['defaultPageSize'])) {
            $value['defaultPageSize'] = 50;
        }

        parent::setPagination($value);
    }
}
