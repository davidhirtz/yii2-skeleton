<?php

declare(strict_types=1);

/**
 * @see AccountController::actionUpdate()
 * @see AccountController::actionDeauthorize()
 *
 * @var View $this
 * @var AccountUpdateForm $form
 */

use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\models\forms\AccountUpdateForm;
use Hirtz\Skeleton\modules\admin\controllers\AccountController;
use Hirtz\Skeleton\modules\admin\widgets\forms\AccountActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\TwoFactorAuthenticatorActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\grids\AuthClientGridView;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\Alert;
use Hirtz\Skeleton\widgets\forms\DeleteActiveForm;
use Hirtz\Skeleton\widgets\forms\FormContainer;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use Hirtz\Skeleton\widgets\navs\Header;

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
