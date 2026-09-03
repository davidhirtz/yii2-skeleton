<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
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

    protected function configure(): void
    {
        $this->title ??= $this->provider
            ? Lang::t('skeleton', 'COMMON_REDIRECTS')
            : $this->model?->getDisplayName() ?? Lang::t('skeleton', 'REDIRECT_HEADER_CREATE_NEW_REDIRECT');

        if (!$this->provider) {
            $this->breadcrumbs ??= [
                new Breadcrumb(Lang::t('skeleton', 'COMMON_REDIRECTS'), ['/admin/redirect/index']),
            ];
        }

        if ($this->model) {
            $this->addContent($this->getActionDropdown());
        }

        if ($this->provider) {
            $this->addContent($this->getCreateButton());
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->url ??= ['/admin/redirect/index'];
        }

        parent::configure();
    }

    protected function getActionDropdown(): ?Stringable
    {
        return RedirectActionDropdown::make()
            ->model($this->model);
    }

    protected function getCreateButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Lang::t('skeleton', 'COMMON_NEW_REDIRECT'))
            ->url(['/admin/redirect/create']);
    }
}
