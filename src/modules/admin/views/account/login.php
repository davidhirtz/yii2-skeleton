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
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;

$this->setTitle(Yii::t('skeleton', 'Login'));
?>

<?= ErrorSummary::forModel($form)
    ->title(Yii::t('skeleton', 'Login unsuccessful')); ?>

<noscript>
    <?= Html::danger(Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.')); ?>
</noscript>

<?= Container::make()
    ->centered()
    ->html(
        Card::make()
            ->title($this->title)
            ->html(LoginActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'Login with Facebook'))
                ->icon('brand:facebook')
                ->href(['auth', 'authclient' => 'facebook'])
                ->visible($form->isFacebookLoginEnabled()))
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'Create new account'))
                ->icon('user')
                ->href(['create'])
                ->visible(Yii::$app->getUser()->isSignupEnabled()))
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'Resend email confirmation'))
                ->icon('envelope')
                ->href(['resend'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()))
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'I forgot my password'))
                ->icon('key')
                ->href(['recover'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled()))
    ); ?>
