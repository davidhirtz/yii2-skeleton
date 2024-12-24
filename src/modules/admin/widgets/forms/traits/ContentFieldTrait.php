<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use davidhirtz\yii2\skeleton\widgets\forms\TinyMceEditor;

trait ContentFieldTrait
{
    public function contentField($options = []): ActiveField|string
    {
        if ($this->model->contentType) {
            $isHtml = $this->model->contentType === 'html';

            if ($isHtml) {
                $options['labelOptions']['class'] ??= 'col-form-label col-form-content-label';
            }

            $field = $this->field($this->model, 'content', $options);

            return $isHtml
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
