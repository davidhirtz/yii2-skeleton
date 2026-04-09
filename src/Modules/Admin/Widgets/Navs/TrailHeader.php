<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;
use yii\base\Model;

class TrailHeader extends Header
{
    protected ?TrailActiveDataProvider $provider = null;
    protected ?Model $model = null;

    public function provider(TrailActiveDataProvider $provider): static
    {
        $this->provider = $provider;

        if ($provider->model) {
            $this->model = $provider->getModels()
                ? current($provider->getModels())->getModelClass()
                : null;
        }

        return $this->page($provider);
    }

    #[Override]
    public function configure(): void
    {
        $this->title ??= $this->getTrailModelTitle();
        $this->url ??= $this->getTrailModelAdminRoute() ?? ['/admin/trail/index'];

        $this->setBreadcrumbs();
        parent::configure();
    }

    protected function setBreadcrumbs(): void
    {
        if ($this->provider->trailId || $this->model) {
            $this->view->addBreadcrumb(Yii::t('skeleton', 'History'), ['/admin/trail/index']);
        }
    }

    protected function getTrailModelTitle(): string
    {
        if ($this->model instanceof TrailModelInterface) {
            return $this->model->getTrailModelName();
        }

        if ($this->provider->trailId) {
            return Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'History'),
                'id' => $this->provider->trailId,
            ]);
        }

        return Yii::t('skeleton', 'History');
    }

    protected function getTrailModelAdminRoute(): ?array
    {
        if (!$this->model instanceof TrailModelInterface) {
            return null;
        }

        $route = $this->model->getTrailModelAdminRoute();
        $language = explode('::', (string)$this->model->getTrailBehavior()->modelClass)[1] ?? null;

        return $route ? [...$route, 'language' => $language] : null;
    }
}
