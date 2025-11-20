<?php

declare(strict_types=1);

/**
 * @see AccountController::actionUpdate()
 * @see AccountController::actionDeauthorize()
 *
 * @var View $this
 * @var AccountUpdateForm $form
 */

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AuthClientGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\Alert;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use davidhirtz\yii2\skeleton\widgets\navs\Header;

$this->title(Yii::t('skeleton', 'Account'));

echo Header::make()
    ->title($form->user->getUsername());

if ($form->user->isUnconfirmed()) {
    echo Container::make()
        ->content(Alert::make()
            ->warning()
            ->content(Yii::t('skeleton', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
                'email' => $form->user->email,
                'here' => Html::a(Yii::t('skeleton', 'here'), ['resend']),
            ])));
}

echo FormContainer::make()
    ->title($this->title)
    ->form(AccountActiveForm::make()
        ->model($form));

if (Yii::$app->getUser()->enableGoogleAuthenticator) {
    echo FormContainer::make()
        ->title(Yii::t('skeleton', 'Google Authenticator'))
        ->form(GoogleAuthenticatorActiveForm::widget([
            'model' => GoogleAuthenticatorForm::create([
                'user' => $form->user,
            ]),
        ]));
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
