<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemAction;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));
?>

<?= Html::errorSummary($form, ['header' => Yii::t('skeleton', 'Your password could not be reset')]); ?>

<?= Container::tag()
    ->addContent(Card::tag()
        ->title($this->title)
        ->body(PasswordRecoverActiveForm::widget([
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