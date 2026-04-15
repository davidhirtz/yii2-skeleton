<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Stringable;
use Yii;

class RedirectHeader extends Header
{
    /**
     * @use ModelTrait<Redirect|null>
     */
    use ModelTrait;

    /**
     * @use ProviderTrait<RedirectActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Configure]
    protected function configureDefaults(): void
    {
        $this->title ??= $this->provider
            ? Yii::t('skeleton', 'Redirects')
            : $this->model?->getDisplayName() ?? Yii::t('skeleton', 'Create New Redirect');

        if (!$this->provider) {
            $this->breadcrumbs ??= [Yii::t('skeleton', 'Redirects') => ['/admin/redirect/index']];
        }

        if ($this->model) {
            $this->addContent($this->getActionDropdown());
        }

        if ($this->provider) {
            $this->addContent($this->getCreateButton());
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->url ??= ['/admin/redirect/index'];
        }
    }

    protected function getActionDropdown(): ?Stringable
    {
        return RedirectActionDropdown::make()
            ->model($this->model);
    }

    protected function getCreateButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('skeleton', 'New Redirect'))
            ->url(['/admin/redirect/create']);
    }
}
