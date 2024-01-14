<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Yii;
use yii\base\Model;

class TrailSubmenu extends Submenu
{
    public ?TrailActiveDataProvider $dataProvider = null;
    private ?Model $_trailModel = null;

    public function init(): void
    {
        if ($this->title === null) {
            $model = $this->getTrailModel();

            if ($model) {
                $name = $model->getTrailModelName();
                $this->title = ($route = $this->getTrailModelAdminRoute()) ? Html::a($name, $route) : $name;
            } else {
                $this->title = Html::a(Yii::t('skeleton', 'History'), ['index']);
            }
        }

        $this->setBreadcrumbs();
        parent::init();
    }

    public function setBreadcrumbs(): void
    {
        $view = $this->getView();

        if ($this->dataProvider->trailId) {
            $view->setBreadcrumb(Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'History'),
                'id' => $this->dataProvider->trailId,
            ]));
        }

        $model = $this->getTrailModel();

        if ($model) {
            $view->setBreadcrumb($model->getTrailModelName());
        }
    }

    public function getTrailModelAdminRoute(): array|false
    {
        $model = $this->getTrailModel();

        if ($route = $model?->getTrailModelAdminRoute()) {
            /** @var TrailBehavior $behavior */
            $behavior = $model->getBehavior('TrailBehavior');

            return [
                ...$route,
                'language' => explode('::', $behavior->modelClass)[1] ?? null
            ];
        }

        return false;
    }

    /**
     * @return TrailBehavior|Model|null
     */
    public function getTrailModel(): mixed
    {
        if ($this->dataProvider->model) {
            $this->_trailModel ??= $this->dataProvider->getModels()
                ? current($this->dataProvider->getModels())->getModelClass()
                : null;
        }

        return $this->_trailModel;
    }
}
