<?php

declare(strict_types=1);

/**
 * @see AccountController::actionUpdate()
 *
 * @var View $this
 * @var AccountUpdateForm $form
 */

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\AccountActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('skeleton', 'COMMON_SETTINGS'));

echo AccountHeader::make()
    ->model($form->user);

echo AccountSubmenu::make();

if ($form->user->isUnconfirmed()) {
    echo Container::make()
        ->content(Alert::make()
            ->warning()
            ->content(Yii::t('skeleton', 'ACCOUNT_UPDATE_UNCONFIRMED_EMAIL', [
                'email' => $form->user->email,
                'here' => A::make()
                    ->text(Yii::t('skeleton', 'ACCOUNT_UPDATE_UNCONFIRMED_EMAIL_LINK'))
                    ->href(['resend']),
            ])));
}

echo FormContainer::make()
    ->form(AccountActiveForm::make()
        ->model($form));
