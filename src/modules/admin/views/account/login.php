<?php

declare(strict_types=1);

/**
 * @see AccountController::actionLogin()
 * @see AccountController::actionResend()
 * @see AccountController::actionRecover()
 *
 * @var View $this
 * @var LoginForm $form
 */

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemAction;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\web\View;

$this->setTitle(Yii::t('skeleton', 'Login'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Login unsuccessful'),
]); ?>

<noscript>
    <div class="alert alert-danger">
        <div class="alert-heading"><?= Yii::t('skeleton', 'JavaScript is disabled on your browser.'); ?></div>
        <p><?= Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
    </div>
</noscript>

<?= Container::tag()
    ->addContent(Card::tag()
        ->title($this->title)
        ->body(LoginActiveForm::widget([
            'model' => $form,
        ])))
    ->addContent(
        ListGroup::tag()
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'Login with Facebook'))
            ->icon('brand:facebook')
            ->href(['auth', 'authclient' => 'facebook'])
            ->visible($form->isFacebookLoginEnabled()))
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'Create new account'))
            ->icon('user')
            ->href(['create'])
            ->visible(Yii::$app->getUser()->isSignupEnabled()))
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'Resend email confirmation'))
            ->icon('envelope')
            ->href(['resend'])
            ->visible(Yii::$app->getUser()->isPasswordResetEnabled() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()))
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'I forgot my password'))
            ->icon('key')
            ->href(['recover'])
            ->visible(Yii::$app->getUser()->isPasswordResetEnabled()))
    )
    ->centered()
    ->render(); ?>
