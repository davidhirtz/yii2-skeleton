<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\RedirectActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\RedirectHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo RedirectHeader::make();

echo FormContainer::make()
    ->title(Yii::t('skeleton', 'New Redirect'))
    ->form(RedirectActiveForm::make()
        ->model($redirect));
