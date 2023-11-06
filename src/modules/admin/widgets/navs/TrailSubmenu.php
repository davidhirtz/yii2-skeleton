<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Yii;

class TrailSubmenu extends Submenu
{
    /**
     * @var TrailActiveDataProvider
     */
    public $dataProvider;

    /**
     * @var ActiveRecord
     */
    private $_trailModel;

    /**
     * Initializes the nav items.
     */
    public function init(): void
    {
        if (!$this->title) {
            if ($model = $this->getTrailModel()) {
                $name = $model->getTrailModelName();
                $this->title = ($route = $this->getTrailModelAdminRoute()) ? Html::a($name, $route) : $name;
            } else {
                $this->title = Html::a(Yii::t('skeleton', 'History'), ['index']);
            }
        }

        $this->setBreadcrumbs();
        parent::init();
    }

    /**
     * Sets breadcrumbs.
     */
    public function setBreadcrumbs()
    {
        $view = $this->getView();

        if ($this->dataProvider->trailId) {
            $view->setBreadcrumb(Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'History'),
                'id' => $this->dataProvider->trailId,
            ]));
        }

        if ($model = $this->getTrailModel()) {
            $view->setBreadcrumb($model->getTrailModelName());
        }
    }

    /**
     * @return array|false
     */
    public function getTrailModelAdminRoute()
    {
        if (($model = $this->getTrailModel()) && ($route = $model->getTrailModelAdminRoute())) {
            return array_merge($route, ['language' => explode('::', $model->modelClass)[1] ?? null]);
        }

        return false;
    }

    /**
     * @return TrailBehavior
     */
    public function getTrailModel()
    {
        if ($this->_trailModel === null && $this->dataProvider->model) {
            $this->_trailModel = $this->dataProvider->getModels() ? current($this->dataProvider->getModels())->getModelClass() : null;
        }

        return $this->_trailModel;
    }
}