<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use Yii;
use yii\helpers\Inflector;

/**
 * Class ActiveFormTrait.
 * @package davidhirtz\yii2\skeleton\widgets\form
 *
 * @method ActiveField field($model, $attribute, $options = [])
 */
trait ActiveFormTrait
{
    /**
     * @var \davidhirtz\yii2\skeleton\db\ActiveRecord
     */
    public $model;

    /**
     * @var array containing a list of fields that will be auto-generated.
     * @see ActiveFormTrait::renderFields()
     */
    public $fields;

    /**
     * @var bool whether fields for unsafe attributes should be generated.
     */
    public $showUnsafeAttributes = false;

    /**
     * @var array containing the button config for the button row.
     * @see ActiveFormTrait::renderButtons()
     */
    public $buttons;

    /**
     * @var bool whether a default submit button should be displayed.
     */
    public $showSubmitButton = true;

    /**
     * @var array containing a custom list of languages used for i18n aware attributes.
     * Leave empty to use default languages.
     */
    public $languages;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->model) {
            if ($this->languages === null && $this->model->i18nAttributes) {
                $this->languages = Yii::$app->getI18n()->getLanguages();
            }
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->model) {
            $this->renderFields();
            $this->renderButtons();
        }

        return parent::run();
    }

    /**
     * Renders the configured form fields.
     */
    public function renderFields()
    {
        if ($this->fields) {

            $safeAttributes = $this->model->safeAttributes();

            foreach ($this->fields as $fieldConfig) {

                $fieldConfig = (array)$fieldConfig;
                $attribute = array_shift($fieldConfig);

                if ($this->showUnsafeAttributes || in_array($attribute, $safeAttributes)) {

                    // Horizontal line.
                    if ($attribute == '-') {
                        echo $this->renderHorizontalLine();
                        continue;
                    }

                    // Custom field.
                    if (!isset($fieldConfig[0]) || is_array($fieldConfig[0])) {
                        $methodName = lcfirst(Inflector::camelize($attribute)) . 'Field';

                        if (method_exists($this, $methodName)) {
                            echo call_user_func_array([$this, $methodName], $fieldConfig);
                            continue;
                        }
                    }

                    // Auto-generated field.
                    $options = isset($fieldConfig[0]) && is_array($fieldConfig[0]) ? array_shift($fieldConfig) : [];
                    $field = $this->field($this->model, $attribute, $options);

                    $type = isset($fieldConfig[0]) ? $fieldConfig[0] : 'text';
                    $inputOptions = isset($fieldConfig[1]) ? $fieldConfig[1] : [];

                    if (in_array($type, ['email', 'number', 'password', 'text'])) {
                        $field->input($type, $inputOptions);
                    } elseif (method_exists($field, $type)) {
                        $field->{$type}($inputOptions);
                    } elseif (method_exists($this, $type)) {
                        $this->{$type}($inputOptions);
                    } else {
                        $field->widget($type, $inputOptions);
                    }

                    // Prevent empty field rows for auto-generated fields.
                    if($field->parts['{input}'])
                    {
                        echo in_array($attribute, $this->model->i18nAttributes) ? implode("\n", $this->i18nAttributeFields($field)) : $field;
                    }
                }
            }
        }
    }

    /**
     * Renders the configured buttons.
     */
    public function renderButtons()
    {
        echo $this->buttonRow($this->buttons ?: ($this->showSubmitButton ? $this->button() : null));
    }

    /**
     * @param ActiveField $field
     * @return ActiveField[]
     */
    public function i18nAttributeFields($field)
    {
        $i18n = Yii::$app->getI18n();
        $fields = [];

        if (!$field->languages === null) {
            $field->languages = $this->languages;
        }

        foreach ($field->languages as $language) {
            if ($language != Yii::$app->sourceLanguage) {
                $fields[] = $clone = clone $field;
                $clone->attribute = $i18n->getAttributeName($field->attribute, $language);
            }
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function renderHorizontalLine()
    {
        return '<hr>';
    }

    /**
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord|string $label
     * @param array $options
     *
     * @return string
     */
    public function button($label = null, $options = ['class' => 'btn-primary'])
    {
        if (!$label) {
            $label = $this->model->getIsNewRecord() ? Yii::t('skeleton', 'Create') : Yii::t('skeleton', 'Update');
        }

        if (!isset($options['type'])) {
            $options['type'] = 'submit';
        }

        Html::addCssClass($options, ['btn', 'btn-submit']);
        return Html::button($label, $options);
    }

    /**
     * @param array|string $buttons
     * @param array $options
     * @return string
     */
    public function buttonRow($buttons, $options = [])
    {
        return $buttons ? $this->row($this->offset(Html::buttons($buttons, $options)), ['class' => 'form-group-buttons']) : null;
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function textRow($content, $options = [])
    {
        return $this->row($this->offset(Html::formText($content, $options)));
    }

    /**
     * @param array $items
     * @param array $options
     * @return string
     */
    public function listRow($items, $options = [])
    {
        if (!$options) {
            $options = [
                'class' => 'list-unstyled small text-muted',
                'encode' => false,
            ];
        }

        return $this->renderHorizontalLine() . $this->textRow(Html::ul($items, $options));
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function offset($content, $options = [])
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['offset']);
        return Html::tag('div', $content, $options);
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function row($content, $options = [])
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['field']);
        return Html::tag('div', $content, $options);
    }
}