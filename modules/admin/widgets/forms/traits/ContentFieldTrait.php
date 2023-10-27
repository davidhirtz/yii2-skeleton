<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\widgets\forms\TinyMceEditor;
use yii\helpers\ArrayHelper;

/**
 * ContentFieldTrait provides methods to manipulate the CKEditor.
 */
trait ContentFieldTrait
{
    public function contentField($options = []): string
    {
        if ($this->model->contentType) {
            $attribute = $this->model->getI18nAttributeName('content', ArrayHelper::remove($options, 'language'));
            $field = $this->field($this->model, $attribute, $options);

            return $this->model->contentType === 'html' ? $field->widget(TinyMceEditor::class, $this->getContentConfig()) : $field->textarea();
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