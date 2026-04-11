<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Stringable;
use Yii;

class RedirectIndexHeader extends Header
{
    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'Redirects');
        $this->url ??= ['/admin/redirect/index'];

        $this->addContent($this->getCreateButton());

        parent::configure();
    }

    protected function getCreateButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('skeleton', 'New Redirect'))
            ->url(['/admin/redirect/create']);
    }
}
