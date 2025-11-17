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
use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AuthClientsGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use davidhirtz\yii2\skeleton\widgets\navs\Header;

$this->setTitle(Yii::t('skeleton', 'Account'));

echo Header::make()
    ->title($form->user->getUsername());

if ($form->user->isUnconfirmed()) {
    echo Html::warning(Yii::t('skeleton', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
        'email' => $form->email,
        'here' => Html::a(Yii::t('skeleton', 'here'), ['resend']),
    ]));
}

echo ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'Your account could not be updated'));

echo FormContainer::make()
    ->title($this->title)
    ->form(AccountActiveForm::widget([
        'model' => $form,
    ]));

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
        ->grid(AuthClientsGridView::make()
            ->user($form->user));
}
if ($form->user->isDeletable()) {
    echo FormContainer::make()
        ->title(Yii::t('skeleton', 'Delete Account'))
        ->danger()
        ->form(DeleteActiveForm::widget([
            'model' => $form->user,
            'attribute' => 'password',
            'action' => ['delete'],
            'message' => Yii::t('skeleton', 'Type your password in the text field below to delete your account, all related items and uploaded files. This cannot be undone, please be certain!'),
            'fieldOptions' => [
                'inputOptions' => [
                    'type' => 'password',
                ],
            ],
        ]));
} elseif ($form->user->isOwner()) {
    echo Container::make()
        ->content(Alert::make()
            ->text(Yii::t('skeleton', 'You cannot delete your account, because you are the owner of this website.'))
            ->warning());
}
