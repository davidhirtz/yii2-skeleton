<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use Yii;
use yii\helpers\Inflector;
use yii\helpers\Json;

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
                $this->languages = array_filter(Yii::$app->getI18n()->getLanguages(), function ($language) {
                    return Yii::$app->language != $language;
                });
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
                if ($fieldConfig instanceof \yii\widgets\ActiveField) {
                    if ($this->showUnsafeAttributes || in_array($fieldConfig->attribute, $safeAttributes)) {
                        echo $fieldConfig;
                        continue;
                    }
                }

                $fieldConfig = (array)$fieldConfig;
                $attribute = array_shift($fieldConfig);

                if ($attribute) {
                    if (isset($fieldConfig[0]['visible'])) {
                        if ($fieldConfig[0]['visible'] === false) {
                            continue;
                        }

                        unset($fieldConfig[0]['visible']);
                    }

                    // Horizontal line.
                    if ($attribute == '-') {
                        echo $this->horizontalLine();
                        continue;
                    }

                    if (isset($this->model->getAttributes()[$attribute])) {
                        if ($this->showUnsafeAttributes || in_array($attribute, $safeAttributes)) {
                            if (($fieldConfig[0] ?? null) === 'hidden') {
                                echo $this->hiddenInputField($this->model, $attribute);
                                continue;
                            }

                            echo $this->getAutogeneratedField($attribute, $fieldConfig);

                            if (in_array($attribute, $this->model->i18nAttributes)) {
                                foreach ($this->languages as $language) {
                                    echo $this->getAutogeneratedField(Yii::$app->getI18n()->getAttributeName($attribute, $language), $fieldConfig);
                                }
                            }
                        }
                    } else {
                        echo $attribute;
                    }
                }
            }
        }
    }

    /**
     * Renders i18n aware hidden input field for given attribute.
     *
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
     * @param string $attribute
     * @return string
     */
    public function hiddenInputField($model, $attribute)
    {
        $fields = Html::activeHiddenInput($model, $attribute);

        if (in_array($attribute, $model->i18nAttributes)) {
            foreach ($this->languages as $language) {
                $fields .= Html::activeHiddenInput($model, Yii::$app->getI18n()->getAttributeName($attribute, $language));
            }
        }

        return $fields;
    }

    /**
     * @param string $attribute
     * @param array $fieldConfig
     * @return ActiveField|string
     */
    protected function getAutogeneratedField($attribute, $fieldConfig = [])
    {
        if (is_array($fieldConfig[0] ?? [])) {
            $methodName = lcfirst(Inflector::camelize($attribute)) . 'Field';

            if (method_exists($this, $methodName)) {
                return call_user_func_array([$this, $methodName], $fieldConfig);
            }
        }

        $options = isset($fieldConfig[0]) && is_array($fieldConfig[0]) ? array_shift($fieldConfig) : [];
        $type = isset($fieldConfig[0]) ? array_shift($fieldConfig) : 'text';

        $field = $this->field($this->model, $attribute, $options);
        $inputOptions = isset($fieldConfig[0]) ? $fieldConfig[0] : [];

        if (in_array($type, ['email', 'number', 'password', 'text'])) {
            $field->input($type, $inputOptions);

        } elseif (method_exists($field, $type)) {
            call_user_func_array([$field, $type], $fieldConfig);

        } elseif (method_exists($this, $type)) {
            call_user_func_array([$this, $type], $fieldConfig);

        } else {
            $field->widget($type, $inputOptions);
        }

//        return $field->parts['{input}'] ? $field : '';
        return $field;
    }

    /**
     * Renders the configured buttons.
     */
    public function renderButtons()
    {
        echo $this->buttonRow($this->buttons ?: ($this->showSubmitButton ? $this->button() : null));
    }

    /**
     * @return string
     * @deprecated as the "render" methods should echo instead of return, please use
     * {@link ActiveFormTrait::horizontalLine()} instead.
     *
     */
    public function renderHorizontalLine()
    {
        return '<hr>';
    }

    /**
     * @return string
     */
    public function horizontalLine()
    {
        return '<hr>';
    }

    /**
     * @param string|null $label
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
     * @param string $content
     * @param array $options
     * @return string
     */
    public function label($content, $options = [])
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['label']);
        return Html::tag('div', $content, $options);
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function input($content, $options = [])
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['input']);
        return Html::tag('div', $content, $options);
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

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function wrapper($content, $options = [])
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['wrapper']);
        return Html::tag('div', $content, $options);
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
     * @param string $label
     * @param string $content
     * @param array $options
     * @return string
     */
    public function labelRow($label, $content, $options = [])
    {
        return $this->row($this->label($label) . $this->wrapper($content), $options);
    }

    /**
     * @param string $label
     * @param string $content
     * @param array $options
     * @return string
     */
    public function plainTextRow($label, $content, $options = [])
    {
        return $this->row($this->label($label) . $this->wrapper(Html::tag('div', $content, ['class' => 'form-control-plaintext'])), $options);
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

        return $this->horizontalLine() . $this->textRow(Html::ul($items, $options));
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
     * Encodes data toggle options of input fields. Attribute names can either be set via the fields
     * key or as an array with the values being the first value and the field names the second.
     *
     * [
     *     'content' => [Section::TYPE_VISUAL],
     *     [[Section::TYPE_VISUAL], ['content'],
     * ],
     *
     * @param array $fields
     * @param array|null $languages
     * @return array
     */
    public function getToggleOptions($fields, $languages = null)
    {
        $options = [];

        foreach ($fields as $name => $values) {
            if (is_int($name)) {
                list($values, $attributes) = $values;
            } else {
                $attributes = [$name];
            }

            $selectors = [];
            foreach (method_exists($this->model, 'getI18nAttributesNames') ? $this->model->getI18nAttributesNames($attributes, $languages) : $attributes as $attribute) {
                $selectors[] = $this->model->hasAttribute($attribute) ? \yii\helpers\Html::getInputId($this->model, $attribute) : $attribute;
            }

            $options[] = [$values, $selectors];
        }

        return ['data-form-toggle' => Json::htmlEncode($options)];
    }
}