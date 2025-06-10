<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionCreate()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\SignupForm $form
 */

use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\modules\admin\widgets\ErrorSummary;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\SignupActiveForm;

$this->setTitle(Yii::t('skeleton', 'Sign up'));
?>

<?= ErrorSummary::make()
    ->models($form)
    ->title(Yii::t('skeleton', 'Your account could not be created'))
    ->render(); ?>

    <noscript>
        <?= Alert::make()
            ->danger()
            ->html(Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'))
            ->render(); ?>
    </noscript>

<?= Container::make()
    ->html(
        Card::make()
            ->title($this->title)
            ->html(SignupActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'Sign up with Facebook'))
                ->icon('brand:facebook')
                ->href(['auth', 'authclient' => 'facebook'])
                ->visible($form->isFacebookSignupEnabled()))
            ->addLink(ListGroupItemLink::make()
                ->text(Yii::t('skeleton', 'Back to login'))
                ->href(['login'])
                ->icon('sign-in-alt'))
    )
    ->centered()
    ->render(); ?>