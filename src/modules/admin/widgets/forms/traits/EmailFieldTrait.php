<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use Yii;
use yii\widgets\ActiveField;

trait EmailFieldTrait
{
    use SubmitButtonTrait;

    public function emailField(array $options = []): ActiveField|string
    {
        $field = $this->field($this->model, 'email', [
            'icon' => 'envelope',
            'enableError' => false,
        ]);

        return $field->textInput([
            ...$options,
            'autocomplete' => 'username',
            'autofocus' => !$this->model->hasErrors(),
            'type' => 'email',
        ]);
    }

    public function sendEmailButton(): string
    {
        return $this->submitButton(Yii::t('skeleton', 'Send Email'));
    }
}
