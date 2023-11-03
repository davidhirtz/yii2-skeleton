<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\widgets\ActiveField;

trait ActiveFormTrait
{
    public ?Model $model = null;

    /**
     * @var array|null containing a list of fields that will be auto-generated. This can either be a plain list of
     * attribute names, a list of field configurations, a callback or a combination of those.
     */
    public ?array $fields = null;

    /**
     * @var bool whether fields for unsafe attributes should be generated.
     */
    public bool $showUnsafeAttributes = false;

    /**
     * @var array|string|null containing the button config for the button row.
     * @see ActiveFormTrait::renderButtons()
     */
    public array|string|null $buttons = null;

    /**
     * @var bool whether a default submit button should be displayed.
     */
    public bool $showSubmitButton = true;

    /**
     * @var bool whether bottom buttons should be sticky
     */
    public bool $hasStickyButtons = false;

    /**
     * @var array|null containing a custom list of languages used for i18n-aware attributes.
     * Leave empty to use default languages.
     */
    public ?array $languages = null;

    /**
     * @var array|null containing a custom list of attribute names used for i18n-aware attributes. Defaults to the
     * model's i18nAttributes.
     */
    public ?array $i18nAttributes = null;

    public function init(): void
    {
        if ($this->model) {
            $this->i18nAttributes ??= $this->model instanceof ActiveRecord ? $this->model->i18nAttributes : [];

            if ($this->i18nAttributes) {
                $this->languages ??= Yii::$app->getI18n()->getLanguages();
            }

            if (!$this->getId(false)) {
                $this->setId(strtolower($this->model->formName()));
            }
        }

        parent::init();
    }

    public function run(): string
    {
        if ($this->model) {
            $this->renderHeader();
            $this->renderFields();
            $this->renderButtons();
            $this->renderFooter();
        }

        return parent::run();
    }

    public function renderFields(): void
    {
        if ($this->fields) {
            foreach ($this->fields as $form) {
                $attribute = null;

                if (is_callable($form)) {
                    continue;
                }

                if (is_string($form)) {
                    $form = [$form];
                }

                if (is_array($form)) {
                    $attribute = array_shift($form);
                }

//                if ($form instanceof ActiveField) {
//                    $attribute = $form->attribute;
//                }

                if (!$attribute) {
                    continue;
                }

                if ($attribute == '-') {
                    echo $this->horizontalLine();
                    continue;
                }

                if (!$this->showUnsafeAttributes && !in_array($attribute, $this->model->safeAttributes())) {
                    Yii::warning("Skipping unsafe attribute '$attribute'.", __METHOD__);
                    continue;
                }

                if (is_array($form)) {
                    $isVisible = !empty($form[0])
                        ? ArrayHelper::remove($form[0], 'visible', true)
                        : ArrayHelper::remove($form, 'visible', true);

                    if (!$isVisible) {
                        continue;
                    }
                }

//                if (!($form instanceof ActiveField || ($form->parts['{input}'] ?? false))) {
//                    continue;
//                }

                $i18nAttributes = method_exists($this->model, 'getI18nAttributesNames')
                    ? $this->model->getI18nAttributesNames($attribute)
                    : [$attribute];

                foreach ($i18nAttributes as $i18nAttribute) {
                    $form['options']['attribute'] = $i18nAttribute;
                    echo $this->createFieldInternal($attribute, $form);
                }
            }
        }
    }

    protected function createFieldInternal(string $attribute, ?array $options): ActiveField|string
    {
        $methodName = lcfirst(Inflector::camelize($attribute)) . 'Field';

        if ($this->hasMethod($methodName)) {
            return call_user_func_array([$this, $methodName], array_filter($options));
        }

        $fieldOptions = isset($options[0]) && is_array($options[0]) ? array_shift($options) : [];
        $type = isset($options[0]) ? array_shift($options) : 'text';

        if ($type === 'hidden') {
            $type = 'hiddenInput';
        }

        $field = $this->field($this->model, $attribute, $fieldOptions);
        $inputOptions = $options[0] ?? [];

        if (in_array($type, ['email', 'number', 'password', 'text'])) {
            $field->input($type, $inputOptions);
        } elseif (method_exists($field, $type)) {
            call_user_func_array([$field, $type], $options);
        } elseif (method_exists($this, $type)) {
            call_user_func_array([$this, $type], $options);
        } else {
            $field->widget($type, $inputOptions);
        }

        return $field;
    }

    public function renderButtons(): void
    {
        $buttonRow = $this->buttonRow($this->buttons ?: ($this->showSubmitButton ? $this->button() : null));
        echo $buttonRow && $this->hasStickyButtons ? Html::tag('div', $buttonRow, ['class' => 'form-group-sticky']) : $buttonRow;
    }

    /**
     * Renders the footer.
     */
    public function renderHeader(): void
    {
    }

    /**
     * Renders the footer.
     */
    public function renderFooter(): void
    {
    }

    /**
     * @return string
     * @deprecated as the "render" methods should echo instead of return, please use
     * {@link ActiveFormTrait::horizontalLine()} instead.
     *
     */
    public function renderHorizontalLine(): string
    {
        return '<hr>';
    }

    /**
     * @return string
     */
    public function horizontalLine(): string
    {
        return '<hr>';
    }

    public function button(?string $label = null, array $options = ['class' => 'btn-primary']): string
    {
        if (!$label) {
            $label = $this->model instanceof ActiveRecord && $this->model->getIsNewRecord() ? Yii::t('skeleton', 'Create') : Yii::t('skeleton', 'Update');
        }

        if (!isset($options['type'])) {
            $options['type'] = 'submit';
        }

        Html::addCssClass($options, ['btn', 'btn-submit']);
        return Html::button($label, $options);
    }

    public function field($model, $attribute, $options = []): ActiveField
    {
        $attribute = ArrayHelper::remove($options, 'attribute', $attribute);
        return parent::field($model, $attribute, $options);
    }

    public function label(string $content, array $options = []): string
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['label']);
        return Html::tag('div', $content, $options);
    }

    public function input(string $content, array $options = []): string
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['input']);
        return Html::tag('div', $content, $options);
    }

    public function offset(string $content, array $options = []): string
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['offset']);
        return Html::tag('div', $content, $options);
    }

    public function row(string $content, array $options = []): string
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['field']);
        return Html::tag('div', $content, $options);
    }

    public function wrapper(string $content, array $options = []): string
    {
        Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['wrapper']);
        return Html::tag('div', $content, $options);
    }

    public function buttonRow(array|string $buttons, array $options = []): string
    {
        return $buttons
            ? $this->row($this->offset(Html::buttons($buttons, $options)), ['class' => 'form-group-buttons'])
            : '';
    }

    public function labelRow(string $label, string $content, array $options = []): string
    {
        return $this->row($this->label($label) . $this->wrapper($content), $options);
    }

    public function plainTextRow(string $label, string $content, array $options = []): string
    {
        return $this->row($this->label($label)
            . $this->wrapper(Html::tag('div', $content, ['class' => 'form-control-plaintext'])), $options);
    }

    public function listRow(array $items, array $options = []): string
    {
        if (!$options) {
            $options = [
                'class' => 'list-unstyled small text-muted',
                'encode' => false,
            ];
        }

        return $this->textRow(Html::ul($items, $options));
    }

    public function textRow(string $content, array $options = []): string
    {
        return $this->row($this->offset(Html::formText($content, $options)));
    }

    /**
     * Encodes data toggle options of input fields. Attribute names can either be set via the fields key or as an array
     * with the values being the first value and the field names the second. Set languages to `false` to disable i18n.
     *
     * [
     *     'content' => [Section::TYPE_VISUAL],
     *     [[Section::TYPE_VISUAL], ['content'],
     * ],
     */
    public function getToggleOptions(array $fields, array|false|null $languages = null): array
    {
        $options = [];

        foreach ($fields as $name => $values) {
            if (is_int($name)) {
                [$values, $attributes] = $values;
            } else {
                $attributes = [$name];
            }

            if (method_exists($this->model, 'getI18nAttributesNames') && $languages !== false) {
                $attributes = $this->model->getI18nAttributesNames($attributes, $languages);
            }

            $selectors = [];

            foreach ($attributes as $attribute) {
                $selectors[] = $this->model->hasProperty($attribute) ? Html::getInputId($this->model, $attribute) : $attribute;
            }

            $options[] = [$values, $selectors];
        }

        return ['data-form-toggle' => Json::htmlEncode($options)];
    }
}