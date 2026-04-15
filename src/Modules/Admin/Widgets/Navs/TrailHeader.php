<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Yii;

class TrailHeader extends Header
{
    use ModelTrait;

    /**
     * @use ProviderTrait<TrailActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Configure]
    public function configureDefaults(): void
    {
        if ($this->provider->model) {
            $this->model = $this->provider->getModels()
                ? current($this->provider->getModels())->getModelClass()
                : null;
        }

        $this->title ??= $this->getTrailModelTitle();
        $this->url ??= $this->getTrailModelAdminRoute() ?? ['/admin/trail/index'];

        if ($this->provider->trailId || $this->model) {
            $this->breadcrumbs ??= [Yii::t('skeleton', 'History') => ['/admin/trail/index']];
        }

        if ($this->provider) {
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
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
