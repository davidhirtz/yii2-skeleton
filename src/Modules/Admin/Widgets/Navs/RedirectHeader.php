<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Stringable;
use Yii;

class RedirectHeader extends Header
{
    protected ?Redirect $model = null;

    public function model(Redirect $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->breadcrumbs ??= [Yii::t('skeleton', 'Redirects') => ['/admin/redirect/index']];
        $this->title ??= $this->model?->getDisplayName() ?? Yii::t('skeleton', 'Update Redirect');

        if ($this->model) {
            $this->addContent($this->getActionDropdown());
        }

        parent::configure();
    }

    protected function getActionDropdown(): ?Stringable
    {
        return RedirectActionDropdown::make()
            ->model($this->model);
    }
}
