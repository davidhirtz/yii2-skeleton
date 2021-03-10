<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data\base;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use yii\data\ActiveDataProvider;

/**
 * Class TrailActiveDataProvider
 * @package davidhirtz\yii2\skeleton\modules\admin\data\base
 * @see \davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider
 *
 * @method Trail[] getModels()
 */
class TrailActiveDataProvider extends ActiveDataProvider
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $model;

    /**
     * @var string
     */
    public $modelId;

    /**
     * @var int
     */
    public $trailId;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->query = Trail::find()
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id');

        if ($this->trailId) {
            $this->query->andWhere(['id' => $this->trailId]);
        }

        if ($this->user) {
            $this->query->andWhere(['user_id' => $this->user->id]);
        } else {
            $this->query->with([
                'user' => function (UserQuery $query) {
                    $query->selectListAttributes();
                }
            ]);
        }

        if ($this->model) {
            $this->query->andFilterWhere([
                'model' => $this->model,
                'model_id' => $this->modelId,
            ]);
        }

        $this->setSort(false);
        $this->getPagination()->defaultPageSize = 50;
        $this->getPagination()->pageSizeLimit = false;

        parent::init();
    }

    /**
     * @return Trail[]
     */
    protected function prepareModels()
    {
        /** @var Trail[] $models */
        $models = parent::prepareModels();

        if ($this->user) {
            foreach ($models as $model) {
                $model->populateRelation('user', $this->user);
            }
        }

        return $models;
    }
}