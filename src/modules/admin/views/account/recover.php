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
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));
?>

<?= Html::errorSummary($form, ['header' => Yii::t('skeleton', 'Your password could not be reset')]); ?>

<?= Container::make()
    ->html(
        Card::make()
        ->title($this->title)
        ->html(PasswordRecoverActiveForm::widget([
            'model' => $form,
        ])),
        ListGroup::make()
            ->addItem(
                ListGroupItemLink::make()
                    ->icon('sign-in-alt')
                    ->href(Url::to(['login']))
                    ->text(Yii::t('skeleton', 'Back to login'))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    )
    ->centered()
    ->render(); ?>