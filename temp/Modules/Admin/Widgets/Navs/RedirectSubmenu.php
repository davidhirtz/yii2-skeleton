<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Stringable;
use Yii;

class RedirectSubmenu extends Submenu
{
    #[\Override]
    protected function renderContent(): string|Stringable
    {
        $this->view->title($this->title)
            ->addBreadcrumb($this->title, ['index']);

        $this->title = Yii::t('skeleton', 'Redirects');
        $this->url = ['index'];

        return parent::renderContent();
    }
}
