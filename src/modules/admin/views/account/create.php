<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionCreate()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\SignupForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemAction;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\SignupActiveForm;

$this->setTitle(Yii::t('skeleton', 'Sign up'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your account could not be created'),
]); ?>

    <noscript>
        <div class="alert alert-danger">
            <p><?php echo Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
        </div>
    </noscript>

<?= Container::tag()
    ->addContent(Card::tag()
        ->title($this->title)
        ->body(SignupActiveForm::widget([
            'model' => $form,
        ])))
    ->addContent(
        ListGroup::tag()
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'Sign up with Facebook'))
            ->icon('brand:facebook')
            ->href(['auth', 'authclient' => 'facebook'])
            ->visible($form->isFacebookSignupEnabled()))
        ->item(ListGroupItemAction::tag()
            ->content(Yii::t('skeleton', 'Back to login'))
            ->href(['login'])
            ->icon('sign-in-alt'))
    )
    ->centered()
    ->render(); ?>