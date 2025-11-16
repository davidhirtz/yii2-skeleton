<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
use Yii;
use yii\base\Model;

class TrailSubmenu extends Submenu
{
    public ?TrailActiveDataProvider $dataProvider = null;
    private ?Model $_trailModel = null;

    #[\Override]
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

        if ($model instanceof TrailModelInterface) {
            return [
                ...$model->getTrailModelAdminRoute(),
                'language' => explode('::', (string) $model->getTrailBehavior()->modelClass)[1] ?? null
            ];
        }

        return false;
    }

    public function getTrailModel(): TrailModelInterface|Model|null
    {
        if ($this->dataProvider->model) {
            $this->_trailModel ??= $this->dataProvider->getModels()
                ? current($this->dataProvider->getModels())->getModelClass()
                : null;
        }

        return $this->_trailModel;
    }
}
