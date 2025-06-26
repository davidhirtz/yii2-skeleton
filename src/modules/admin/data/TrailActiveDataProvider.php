<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use yii\data\ActiveDataProvider;

/**
 * @method Trail[] getModels()
 */
class TrailActiveDataProvider extends ActiveDataProvider
{
    public ?User $user = null;
    public ?string $model = null;
    public ?string $modelId = null;
    public ?int $trailId = null;

    #[\Override]
    public function init(): void
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
                'user' => function (UserQuery $query): void {
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

    #[\Override]
    protected function prepareModels(): array
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
