<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use yii\helpers\ArrayHelper;

/**
 * ContentFieldTrait provides methods to manipulate the CKEditor.
 */
trait ContentFieldTrait
{
    /**
     * @var string
     */
    public $ctaCssClassName = 'cta';

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
        $config = [
            'validator' => $this->model->htmlValidator,
            'clientOptions' => $this->model->htmlValidator !== false ? [] : [
                'allowedContent' => true,
            ],
        ];

        if (in_array($this->ctaCssClassName, $this->model->htmlValidator['allowedClasses'] ?? [])) {
            $config = ArrayHelper::merge($config, $this->ctaButtonConfig());
        }

        return $config;
    }

    /**
     * @return array
     */
    protected function ctaButtonConfig(): array
    {
        return [
            'extraButtons' => [
                [
                    'name' => 'Button',
                    'label' => 'Button',
                    'toolbar' => [1 => 'Link'],
                    'icon' => 'linkbutton',
                    'definition' => [
                        'element' => 'a',
                        'attributes' => ['class' => $this->ctaCssClassName],
                    ],
                ],
            ],
        ];
    }
}