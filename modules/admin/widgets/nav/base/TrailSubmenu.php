<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Yii;

/**
 * Class TrailSubmenu
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 */
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
    public function init()
    {
        if (!$this->title) {
            if ($model = $this->getTrailModel()) {
                $name = $model->getTrailModelName();
                $this->title = ($route = $model->getTrailModelAdminRoute()) ? Html::a($name, $route) : $name;
            } else {
                $this->title = Html::a(Yii::t('skeleton', 'Trail'), ['index']);
            }
        }

        $this->setBreadcrumbs();

        parent::init();
    }

    /**
     *
     */
    public function setBreadcrumbs()
    {
        $view = $this->getView();

        if ($this->dataProvider->trailId) {
            $view->setBreadcrumb(Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'Trail'),
                'id' => $this->dataProvider->trailId,
            ]));
        }

        if ($model = $this->getTrailModel()) {
            $view->setBreadcrumb($model->getTrailModelType());
        }
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