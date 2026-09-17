<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Stringable;
use Yii;

/**
 * @extends ModelHeader<Redirect|null>
 */
class RedirectHeader extends ModelHeader
{
    /**
     * @use ProviderTrait<RedirectActiveDataProvider|null>
     */
    use ProviderTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->addSystemBreadcrumb();

        if ($this->model) {
            $this->addContent($this->getActionDropdown());
        }

        if ($this->provider) {
            $this->title ??= Yii::t('skeleton', 'COMMON_REDIRECTS');
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->url ??= ['/admin/redirect/index'];

            $this->addContent($this->getCreateButton());
        }

        if (!$this->model && !$this->provider) {
            $this->title ??= Yii::t('skeleton', 'REDIRECT_HEADER_CREATE_NEW_REDIRECT');
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
            ->label(Yii::t('skeleton', 'COMMON_NEW_REDIRECT'))
            ->url(['/admin/redirect/create']);
    }

    protected function addSystemBreadcrumb(): void
    {
        $this->addBreadcrumb(Yii::t('skeleton', 'COMMON_SYSTEM'), ['/admin/system/index']);
    }
}
