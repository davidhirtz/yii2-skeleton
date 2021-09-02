<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use yii\helpers\ArrayHelper;

/**
 * Trait ContentFieldTrait
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits
 */
trait ContentFieldTrait
{
    /**
     * @param array $options
     * @return string
     */
    public function contentField($options = [])
    {
        if ($this->model->contentType) {
            $attribute = $this->model->getI18nAttributeName('content', ArrayHelper::remove($options, 'language'));
            $field = $this->field($this->model, $attribute, $options);

            return $this->model->contentType === 'html' ? $field->widget(CKEditor::class, $this->getContentConfig()) : $field->textarea();
        }

        return '';
    }

    /**
     * @return array
     */
    protected function getContentConfig(): array
    {
        return [
            'validator' => $this->model->htmlValidator,
            'clientOptions' => $this->model->htmlValidator !== false ? [] : [
                'allowedContent' => true,
            ],
        ];
    }
}