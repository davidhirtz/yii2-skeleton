<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\EmailFieldTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;

class AccountResendConfirmActiveForm extends ActiveForm
{
    use EmailFieldTrait;

    public $enableClientValidation = false;

    public function __construct(public AccountResendConfirmForm $model, $config = [])
    {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'account-resend-confirm-form';
        parent::init();
    }

    public function run(): string
    {
        $this->renderFields();
        return parent::run();
    }

    public function renderFields(): void
    {
        echo $this->helpBlock();
        echo $this->emailField();
        echo $this->sendEmailButton();
    }

    public function helpBlock(): string
    {
        $content = Yii::t('skeleton', 'Enter your email address and we will send you another email to confirm your account.');
        return Html::tag('p', $content);
    }
}
