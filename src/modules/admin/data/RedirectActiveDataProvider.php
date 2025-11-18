<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\User;
use Override;
use yii\data\ActiveDataProvider;

/**
 * @method Redirect[] getModels()
 */
class RedirectActiveDataProvider extends ActiveDataProvider
{
    public ?int $type = null;
    public ?User $user = null;
    public ?string $search = null;

    #[Override]
    public function init(): void
    {
        $this->query = Redirect::find()
            ->indexBy('id');

        if (null !== $this->type) {
            $this->query->andWhere(['type' => $this->type]);
        }

        if ($search = $this->query->sanitizeSearchString($this->search)) {
            $this->query->andWhere('[[request_uri]] LIKE :search OR [[url]] LIKE :search', [
                'search' => "%$search%",
            ]);
        }

        if ($this->user) {
            $this->query->andWhere(['user_id' => $this->user->id]);
        }

        $this->getPagination()->defaultPageSize = 50;
        $this->getPagination()->pageSizeLimit = false;

        $this->getSort()->defaultOrder = ['updated_at' => SORT_DESC];

        parent::init();
    }

    /**
     * @return Redirect[]
     */
    #[Override]
    protected function prepareModels(): array
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
