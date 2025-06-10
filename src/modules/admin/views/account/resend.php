<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\ErrorSummary;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountResendConfirmActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Resend Account Confirmation'));

echo ErrorSummary::make()
    ->models($form)
    ->title(Yii::t('skeleton', 'Your confirmation could not be resend'))
    ->render();

echo Container::make()
    ->centered()
    ->html(
        Card::make()
            ->title($this->title)
            ->html(AccountResendConfirmActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addLink(
                ListGroupItemLink::make()
                    ->text(Yii::t('skeleton', 'Back to login'))
                    ->icon('sign-in-alt')
                    ->visible(Yii::$app->getUser()->getIsGuest())
                    ->href(Url::to(['login']))
            )
    )
    ->render();
