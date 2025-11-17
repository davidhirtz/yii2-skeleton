<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroup;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroupItem;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));

echo ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'Your password could not be reset'));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(PasswordRecoverActiveForm::widget([
                'model' => $form,
            ])),
        ListGroup::make()
            ->addItem(
                ListGroupItem::make()
                    ->label(Yii::t('skeleton', 'Back to login'))
                    ->icon('sign-in-alt')
                    ->url(Url::to(['login']))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    );
