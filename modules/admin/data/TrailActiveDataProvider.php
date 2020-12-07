<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;

/**
 * Class TrailActiveDataProvider
 * @package davidhirtz\yii2\skeleton\modules\admin\data
 */
class TrailActiveDataProvider extends base\TrailActiveDataProvider
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var ActiveRecord
     */
    public $model;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->query = Trail::find()
            ->orderBy(['id' => SORT_DESC]);

        if ($this->user) {
            $this->query->andWhere(['user_id' => $this->user->id]);
        } else {
            $this->query->with([
                'user' => function (UserQuery $query) {
                    $query->selectListAttributes();
                }
            ]);
        }

        $this->setSort(false);
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