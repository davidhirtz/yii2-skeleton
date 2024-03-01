<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\PasswordRecoverForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\EmailFieldTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;

class PasswordRecoverActiveForm extends ActiveForm
{
    use EmailFieldTrait;

    public $enableClientValidation = false;

    public function __construct(public PasswordRecoverForm $model, $config = [])
    {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'password-recover-form';
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
        $content = Yii::t('skeleton', 'Enter your email address and we will send you instructions how to reset your password.');
        return Html::tag('p', $content);
    }
}
