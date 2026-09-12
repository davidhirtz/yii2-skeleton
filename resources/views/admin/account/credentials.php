<?php

declare(strict_types=1);

/**
 * @see AccountController::actionCredentials()
 *
 * @var View $this
 * @var AccountCredentialsForm $form
 */

use Hirtz\Skeleton\Models\Forms\AccountCredentialsForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\AccountCredentialsActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo AccountHeader::make()
    ->model($form->user);

echo AccountSubmenu::make();

echo FormContainer::make()
    ->form(AccountCredentialsActiveForm::make()
        ->model($form));
