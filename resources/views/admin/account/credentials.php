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
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

echo Header::make()
    ->title($form->user->getUsername());

echo AccountSubmenu::make();

echo FormContainer::make()
    ->form(AccountCredentialsActiveForm::make()
        ->model($form));
