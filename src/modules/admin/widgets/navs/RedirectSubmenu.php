<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
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
