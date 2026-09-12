<?php

declare(strict_types=1);

/**
 * @see AccountController::actionSecurity()
 *
 * @var View $this
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\TwoFactorAuthenticatorActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo AccountHeader::make()
    ->model($user);

echo AccountSubmenu::make();

echo FormContainer::make()
    ->form(TwoFactorAuthenticatorActiveForm::make()
        ->model($user));
