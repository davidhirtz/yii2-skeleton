<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemAction;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountResendConfirmActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Resend Account Confirmation'));
?>
<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your confirmation could not be resend'),
]); ?>

<?= Container::tag()
    ->addContent(Card::tag()
        ->title($this->title)
        ->body(AccountResendConfirmActiveForm::widget([
            'model' => $form,
        ])))
    ->addContent(ListGroup::tag()
        ->item(
            ListGroupItemAction::tag()
            ->text(Yii::t('skeleton', 'Back to login'))
            ->icon('sign-in-alt')
            ->visible(Yii::$app->getUser()->getIsGuest())
            ->href(Url::to(['login']))
        ))
    ->centered()
    ->render(); ?>