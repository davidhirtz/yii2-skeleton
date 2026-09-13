<?php

declare(strict_types=1);

/**
 * @see AccountController::actionSecurity()
 *
 * @var View $this
 * @var User $user
 * @var list<string> $recoveryCodes
 */

use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\TwoFactorAuthenticatorActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo AccountHeader::make()
    ->model($user);

echo AccountSubmenu::make();

if ($recoveryCodes) {
    echo Container::make()
        ->content(Alert::make()
            ->warning()
            ->content(
                P::make()->text(Yii::t('skeleton', 'ACCOUNT_SECURITY_RECOVERY_CODES_ONCE')),
                Ul::make()->content(...array_map(
                    fn (string $code): Li => Li::make()->text($code),
                    $recoveryCodes
                )),
            ));
}

echo FormContainer::make()
    ->form(TwoFactorAuthenticatorActiveForm::make()
        ->model($user));
