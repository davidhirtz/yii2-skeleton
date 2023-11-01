<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\User;
use yii\data\ActiveDataProvider;

/**
 * @method Redirect[] getModels()
 */
class RedirectActiveDataProvider extends ActiveDataProvider
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $search;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->query = Redirect::find()
            ->orderBy(['updated_at' => SORT_DESC])
            ->indexBy('id');

        if ($search = $this->query->sanitizeSearchString($this->search)) {
            $this->query->andWhere("[[request_uri]] LIKE :search OR [[url]] LIKE :search", [
                'search' => "%{$search}%",
            ]);
        }

        if ($this->user) {
            $this->query->andWhere(['user_id' => $this->user->id]);
        }

        $this->getPagination()->defaultPageSize = 50;
        $this->getPagination()->pageSizeLimit = false;
        $this->setSort(false);

        parent::init();
    }

    /**
     * @return Redirect[]
     */
    protected function prepareModels()
    {
        /** @var Redirect[] $models */
        $models = parent::prepareModels();

        if ($this->user) {
            foreach ($models as $model) {
                $model->populateRelation('user', $this->user);
            }
        }

        return $models;
    }
}