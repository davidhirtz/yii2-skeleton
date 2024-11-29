<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\data\ActiveDataProvider;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * @property UserQuery|null $query
 * @extends ActiveDataProvider<User>
 */
class UserActiveDataProvider extends ActiveDataProvider
{
    public ?string $searchString = null;

    public function __construct($config = [])
    {
        $this->query = User::find();
        parent::__construct($config);
    }

    protected function prepareQuery(): void
    {
        $this->initQuery();
        parent::prepareQuery();
    }

    protected function initQuery(): void
    {
        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    public function setSort($value): void
    {
        if (is_array($value)) {
            $value['defaultOrder'] ??= ['last_login' => SORT_DESC];
        }

        parent::setSort($value);
    }

    public function setPagination($value): void
    {
        if (is_array($value)) {
            $value['defaultPageSize'] ??= 50;
        }

        parent::setPagination($value);
    }
}
