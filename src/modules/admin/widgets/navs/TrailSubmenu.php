<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class TrailSubmenu extends Submenu
{
    protected ?TrailActiveDataProvider $provider = null;
    private ?Model $_trailModel = null;

    public function provider(TrailActiveDataProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    #[Override]
    public function renderContent(): string|Stringable
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
        return parent::renderContent();
    }

    protected function setBreadcrumbs(): void
    {
        if ($this->provider->trailId) {
            $this->view->addBreadcrumb(Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'History'),
                'id' => $this->provider->trailId,
            ]));
        }

        $model = $this->getTrailModel();

        if ($model) {
            $this->view->addBreadcrumb($model->getTrailModelName());
        }
    }

    protected function getTrailModelAdminRoute(): array|false
    {
        $model = $this->getTrailModel();

        if ($model instanceof TrailModelInterface) {
            return [
                ...$model->getTrailModelAdminRoute(),
                'language' => explode('::', (string)$model->getTrailBehavior()->modelClass)[1] ?? null
            ];
        }

        return false;
    }

    protected function getTrailModel(): TrailModelInterface|Model|null
    {
        if ($this->provider->model) {
            $this->_trailModel ??= $this->provider->getModels()
                ? current($this->provider->getModels())->getModelClass()
                : null;
        }

        return $this->_trailModel;
    }
}
