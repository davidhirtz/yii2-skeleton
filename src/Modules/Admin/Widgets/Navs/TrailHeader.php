<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Yii;
use yii\base\Model;

class TrailHeader extends Header
{
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;

    /**
     * @use ProviderTrait<TrailActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        if ($this->provider->model) {
            $this->model = $this->provider->getModels()
                ? current($this->provider->getModels())->getModelRecord()
                : null;
        }

        $this->title ??= $this->getTrailModelTitle();
        $this->url ??= $this->getTrailModelAdminRoute() ?? ['/admin/trail/index'];

        $this->addSystemBreadcrumb();

        if ($this->provider->trailId || $this->model) {
            $this->addTrailBreadcrumb();
        }

        if ($this->provider) {
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
        }

        parent::configure();
    }

    protected function getTrailModelTitle(): string
    {
        if ($this->model instanceof TrailModelInterface) {
            return $this->model->getAdminName();
        }

        if ($this->provider->trailId) {
            return Yii::t('skeleton', 'MODEL_ID_FORMAT', [
                'model' => Yii::t('skeleton', 'TRAIL_TITLE'),
                'id' => $this->provider->trailId,
            ]);
        }
        return Yii::t('skeleton', 'TRAIL_TITLE');
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function getTrailModelAdminRoute(): ?array
    {
        // A deleted record comes back as a new one with its id, whose page is a 404.
        if (!$this->model instanceof TrailModelInterface || ($this->model instanceof ActiveRecord && $this->model->getIsNewRecord())) {
            return null;
        }

        $route = $this->model->getAdminRoute();
        $language = explode('::', (string)$this->model->getTrailBehavior()->modelClass)[1] ?? null;

        return $route ? [...$route, 'language' => $language] : null;
    }

    protected function addSystemBreadcrumb(): void
    {
        if (!$this->webuser->can(User::AUTH_ROLE_ADMIN) && !$this->webuser->can(User::AUTH_ROLE_MANAGER)) {
            return;
        }

        $this->addBreadcrumb(Yii::t('skeleton', 'SYSTEM_TITLE'), ['/admin/system/index']);
    }

    protected function addTrailBreadcrumb(): void
    {
        $this->addBreadcrumb(Yii::t('skeleton', 'TRAIL_TITLE'), ['/admin/trail/index']);
    }
}
