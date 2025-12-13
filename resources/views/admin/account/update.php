<?php

declare(strict_types=1);

/**
 * @see AccountController::actionUpdate()
 * @see AccountController::actionDeauthorize()
 *
 * @var View $this
 * @var AccountUpdateForm $form
 */

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\AccountActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\TwoFactorAuthenticatorActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthClientGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'Account'));

echo Header::make()
    ->title($form->user->getUsername());

if ($form->user->isUnconfirmed()) {
    echo Container::make()
        ->content(Alert::make()
            ->warning()
            ->content(Yii::t('skeleton', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
                'email' => $form->user->email,
                'here' => A::make()
                    ->text(Yii::t('skeleton', 'here'))
                    ->href(['resend']),
            ])));
}

echo FormContainer::make()
    ->title($this->title)
    ->form(AccountActiveForm::make()
        ->model($form));

if (Yii::$app->getUser()->enableTwoFactorAuthentication) {
    echo FormContainer::make()
        ->title(Yii::t('skeleton', 'Two-Factor Authentication'))
        ->form(TwoFactorAuthenticatorActiveForm::make()
            ->model($form->user));
}

if (Yii::$app->getAuthClientCollection()->clients) {
    echo GridContainer::make()
        ->title(Yii::t('skeleton', 'Clients'))
        ->grid(AuthClientGridView::make()
            ->user($form->user));
}

if ($form->user->isDeletable()) {
    echo FormContainer::make()
        ->title(Yii::t('skeleton', 'Delete Account'))
        ->danger()
        ->form(DeleteActiveForm::make()
            ->model($form->user)
            ->property('password')
            ->message(Yii::t('skeleton', 'Type your password in the text field below to delete your account, all related items and uploaded files. This cannot be undone, please be certain!'))
            ->inputAttributes(['type' => 'password']));
}

if ($form->user->isOwner()) {
    echo Container::make()
        ->content(Alert::make()
            ->text(Yii::t('skeleton', 'You cannot delete your account, because you are the owner of this website.'))
            ->warning());
}
