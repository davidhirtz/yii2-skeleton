<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use yii\data\ActiveDataProvider;

/**
 * @property UserQuery $query
 * @method User[] getModels()
 */
class UserActiveDataProvider extends ActiveDataProvider
{
    /**
     * @var string
     */
    public $searchString;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->query) {
            $this->query = User::find();
        }

        $this->initQuery();
        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    /**
     * @inheritDoc
     */
    public function setSort($value)
    {
        if (is_array($value) && !isset($value['defaultOrder'])) {
            $value['defaultOrder'] = ['last_login' => SORT_DESC];
        }

        parent::setSort($value);
    }

    /**
     * @inheritDoc
     */
    public function setPagination($value)
    {
        if (is_array($value) && !isset($value['defaultPageSize'])) {
            $value['defaultPageSize'] = 50;
        }

        parent::setPagination($value);
    }
}