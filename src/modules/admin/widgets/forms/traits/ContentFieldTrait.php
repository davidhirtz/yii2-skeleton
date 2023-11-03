<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use davidhirtz\yii2\skeleton\widgets\forms\TinyMceEditor;

/**
 * ContentFieldTrait provides methods to manipulate the CKEditor.
 */
trait ContentFieldTrait
{
    public function contentField($options = []): ActiveField|string
    {
        if ($this->model->contentType) {
            $options['labelOptions']['class'] ??= 'col-form-label col-form-content-label col-md-3';

            $field = $this->field($this->model, 'content', $options);

            return $this->model->contentType === 'html'
                ? $field->widget(TinyMceEditor::class, $this->getContentConfig())
                : $field->textarea();
        }

        return '';
    }

    protected function getContentConfig(): array
    {
        foreach ($this->model->getActiveValidators('content') as $validator) {
            if ($validator instanceof HtmlValidator) {
                return [
                    'validator' => $validator,
                ];
            }
        }

        return [
            'validator' => false,
        ];
    }
}