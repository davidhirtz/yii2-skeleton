<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use yii\widgets\ActiveField;

trait EmailFieldTrait
{
    public function emailField(): ActiveField|string
    {
        $field = $this->field($this->model, 'email', [
            'icon' => 'envelope',
            'enableError' => false,
        ]);

        return $field->textInput([
            'autocomplete' => 'username',
            'autofocus' => !$this->model->hasErrors(),
            'type' => 'email',
        ]);
    }
}
