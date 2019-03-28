<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFieldTrait;

/**
 * Class ActiveField.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class ActiveField extends \yii\bootstrap4\ActiveField
{
    use ActiveFieldTrait;

    /**
     * @var string
     */
    public $checkTemplate = '{beginWrapper}<div class="form-check-inline">{input}{label}{error}{hint}</div>{endWrapper}';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->checkHorizontalTemplate = $this->checkTemplate;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function checkbox($options = [], $enclosedByLabel = false)
    {
        $this->labelOptions = []; // Removes label options, class can be removed when extension is fixed...
        return parent::checkbox($options, $enclosedByLabel);
    }

    /**
     * @inheritdoc
     */
    public function fileInput($options = [])
    {
        if (!isset($options['class'])) {
            $options['class'] = 'form-control-file';
        }

        return parent::fileInput($options);
    }


    /**
     * @inheritdoc
     */
    public function dropdownList($items, $options = [])
    {
        if($items || $this->model->isAttributeRequired($this->attribute)) {
            return parent::dropdownList($items, $options);
        }

        // Don't render empty drop down list.
        $this->parts['{input}'] = null;
        return $this;
    }
}