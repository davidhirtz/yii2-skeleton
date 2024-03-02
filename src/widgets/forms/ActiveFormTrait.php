<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use ReflectionFunction;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\helpers\Json;

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
            $this->i18nAttributes ??= property_exists($this->model, 'i18nAttributes') ? $this->model->i18nAttributes : [];

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
            foreach ($this->fields as $options) {
                if (!is_string($options) && is_callable($options)) {
                    $callback = $options;
                    $options = $callback();

                    if ($options instanceof \yii\widgets\ActiveField) {
                        if (!$this->isSafeAttribute($options->attribute)) {
                            $methodName = (new ReflectionFunction($callback))->getName();
                            Yii::warning("Skipping unsafe attribute '$options->attribute' set by '$methodName'.");
                            continue;
                        }

                        echo $options;

                        if (in_array($options->attribute, $this->i18nAttributes)) {
                            $this->getI18nCallback($callback);
                        }
                    }

                    continue;
                }

                $options = (array)$options;
                $attribute = ArrayHelper::remove($options, 'attribute', array_shift($options));

                if (!$attribute) {
                    continue;
                }

                if ($attribute == '-') {
                    echo $this->horizontalLine();
                    continue;
                }

                if (!$this->isSafeAttribute($attribute)) {
                    Yii::warning("Skipping unsafe attribute '$attribute'.");
                    continue;
                }

                if (is_array($options)) {
                    $isVisible = is_array($options[0] ?? null)
                        ? ArrayHelper::remove($options[0], 'visible', true)
                        : ArrayHelper::remove($options, 'visible', true);


                    if (!$isVisible) {
                        continue;
                    }

                    $options = array_filter($options);
                }

                $methodName = lcfirst(Inflector::camelize($attribute)) . 'Field';

                if ($this->hasMethod($methodName)) {
                    Yii::debug("Rendering '$attribute' field with method '$methodName'");
                    $callable = $this->$methodName(...);
                } else {
                    $options['attribute'] = $attribute;
                    $callable = $this->getAutogeneratedField(...);
                }

                echo in_array($attribute, $this->i18nAttributes)
                    ? $this->getI18nCallback($callable, $options)
                    : $callable($options);
            }
        }
    }

    protected function getI18nCallback(callable $callable, ?array $options = []): string
    {
        $reflection = new ReflectionFunction($callable);
        $params = $reflection->getParameters();
        $content = '';

        if (!$params || $params[0]->name !== 'options') {
            $methodName = $reflection->getClosureThis()::class . '::' . $reflection->getName();
            throw new InvalidConfigException("The method '$methodName' must accept an 'options' parameter if used as an I18N attribute.");
        }

        foreach ($this->languages as $language) {
            $options['language'] = $language;
            $content .= $callable($options);
        }

        return $content;
    }

    protected function getAutogeneratedField(array $options = []): ActiveField|string
    {
        $attribute = ArrayHelper::remove($options, 'attribute');

        $type = ArrayHelper::remove($options, 'type');
        $type ??= is_string($options[0] ?? null) ? array_shift($options) : 'text';

        $fieldOptions = ArrayHelper::remove($options, 'fieldOptions', []);
        $field = $this->field($this->model, $attribute, $fieldOptions);

        if ($type == 'hidden') {
            Yii::debug("Rendering hidden input for '$attribute'");
            return $field->hiddenInput()->parts['{input}'];
        }

        if (in_array($type, ['dropDownList', 'select'])) {
            Yii::debug("Rendering select input for '$attribute'");
            $items = ArrayHelper::remove($options, 'items', array_shift($options));
            return $field->dropDownList($items, $options);
        }

        $fieldTypes = [
            'color',
            'date',
            'datetime-local',
            'email',
            'month',
            'number',
            'password',
            'range',
            'tel',
            'text',
            'time',
            'url',
            'week',
        ];

        if (in_array($type, $fieldTypes)) {
            Yii::debug("Rendering '$attribute' active $type field");
            return $field->input($type, $options);
        }

        $owner = method_exists($field, $type) ? $field : (method_exists($this, $type) ? $this : null);

        if ($owner) {
            $fieldClass = $owner::class;
            Yii::debug("Rendering '$attribute' active field with '$fieldClass::$type'");
            $field->$type($options);
        } else {
            Yii::debug("Rendering '$attribute' active field with widget '$type'");
            $field->widget($type, $options);
        }

        // Only return the field if an input was created, otherwise return an empty string.
        return $field->parts['{input}'] ? $field : '';
    }

    public function field($model, $attribute, $options = []): ActiveField|string
    {
        if (method_exists($this->model, 'getI18nAttributeName')) {
            $language = ArrayHelper::remove($options, 'language', Yii::$app->sourceLanguage);
            $attribute = $this->model->getI18nAttributeName($attribute, $language);
        }

        return parent::field($model, $attribute, $options);
    }

    public function renderButtons(): void
    {
        $buttons = $this->buttons ?: ($this->showSubmitButton ? $this->button() : null);

        if ($buttons) {
            $buttonRow = $this->buttonRow($buttons);

            echo $this->hasStickyButtons
                ? Html::tag('div', $buttonRow, ['class' => 'form-group-sticky'])
                : $buttonRow;
        }
    }

    public function renderHeader(): void
    {
    }

    public function renderFooter(): void
    {
    }

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

    public function isSafeAttribute(string $attribute): bool
    {
        return $this->showUnsafeAttributes || in_array($attribute, $this->model->safeAttributes());
    }
}
