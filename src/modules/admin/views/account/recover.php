<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));

echo ErrorSummary::forModel($form)
    ->title(Yii::t('skeleton', 'Your password could not be reset'));

echo Container::make()
    ->centered()
    ->html(
        Card::make()
            ->title($this->title)
            ->html(PasswordRecoverActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addLink(
                ListGroupItemLink::make()
                    ->icon('sign-in-alt')
                    ->href(Url::to(['login']))
                    ->text(Yii::t('skeleton', 'Back to login'))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    );
