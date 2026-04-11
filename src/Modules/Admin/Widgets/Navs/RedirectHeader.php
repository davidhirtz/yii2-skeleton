<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;

class RedirectHeader extends Header
{
    protected ?Redirect $model = null;
    protected ?RedirectActiveDataProvider $provider = null;

    public function model(Redirect $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function provider(RedirectActiveDataProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            $this->breadcrumbs ??= [Yii::t('skeleton', 'Redirects') => ['/admin/redirect/index']];
        }

        if ($this->provider) {
            $this->title ??= Yii::t('skeleton', 'Redirects');
            $this->url ??= ['/admin/redirect/index'];
        }

        parent::configure();
    }
}
