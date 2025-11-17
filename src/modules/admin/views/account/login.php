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
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroup;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroupItem;

$this->setTitle(Yii::t('skeleton', 'Login'));
?>

<?= ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'Login unsuccessful')); ?>

<noscript>
    <?= Html::danger(Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.')); ?>
</noscript>

<?= Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(LoginActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addItem(ListGroupItem::make()
                ->label(Yii::t('skeleton', 'Login with Facebook'))
                ->icon('brand:facebook')
                ->url(['auth', 'authclient' => 'facebook'])
                ->visible($form->isFacebookLoginEnabled()))
            ->addItem(ListGroupItem::make()
                ->label(Yii::t('skeleton', 'Create new account'))
                ->icon('user')
                ->url(['create'])
                ->visible(Yii::$app->getUser()->isSignupEnabled()))
            ->addItem(ListGroupItem::make()
                ->label(Yii::t('skeleton', 'Resend email confirmation'))
                ->icon('envelope')
                ->url(['resend'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()))
            ->addItem(ListGroupItem::make()
                ->label(Yii::t('skeleton', 'I forgot my password'))
                ->icon('key')
                ->url(['recover'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled()))
    ); ?>
