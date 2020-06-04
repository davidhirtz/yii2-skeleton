<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data\base;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use yii\data\ActiveDataProvider;

/**
 * Class UserActiveDataProvider
 * @package davidhirtz\yii2\skeleton\modules\admin\data\base
 * @see \davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider
 *
 * @property UserQuery $query
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
        $this->initQuery();
        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        $this->query = User::find();

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    /**
     * @param array|bool|\yii\data\Sort $value
     */
    public function setSort($value)
    {
        if (is_array($value) && !isset($value['defaultOrder'])) {
            $value['defaultOrder'] = ['last_login' => SORT_DESC];
        }

        parent::setSort($value);
    }

    /**
     * @param array|bool|\yii\data\Pagination $value
     */
    public function setPagination($value)
    {
        if (is_array($value) && !isset($value['defaultPageSize'])) {
            $value['defaultPageSize'] = 50;
        }

        parent::setPagination($value);
    }
}