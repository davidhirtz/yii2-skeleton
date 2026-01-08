<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
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
        $this->title ??= $this->getTrailModel()?->getTrailModelName() ?? Yii::t('skeleton', 'History');
        $this->url ??= $this->getTrailModelAdminRoute() ?? ['index'];

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

    protected function getTrailModelAdminRoute(): ?array
    {
        $model = $this->getTrailModel();

        if ($model instanceof TrailModelInterface) {
            return [
                ...$model->getTrailModelAdminRoute(),
                'language' => explode('::', (string)$model->getTrailBehavior()->modelClass)[1] ?? null
            ];
        }

        return null;
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
