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

    public function __construct(array $config = [])
    {
        $this->title ??= Yii::t('skeleton', 'Redirects');
        $this->url ??= ['/admin/redirect/index'];

        parent::__construct($config);
    }

    public function provider(RedirectActiveDataProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function model(Redirect $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->addBreadcrumbs();
        parent::configure();
    }

    protected function addBreadcrumbs(): void
    {
        if (!$this->provider) {
            $this->view->addBreadcrumb(Yii::t('skeleton', 'Redirects'), $this->url);
        }
    }
}
