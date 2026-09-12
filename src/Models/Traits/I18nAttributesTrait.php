<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Yii;
use yii\validators\UniqueValidator as BaseUniqueValidator;

trait I18nAttributesTrait
{
    /**
     * @var array containing the attribute names of attributes which should be used with I18N features
     */
    public array $i18nAttributes = [];

    private ?array $_i18nHints = null;
    private ?array $_i18nLabels = null;
    private array $_i18nLabelsKey = [];

    /**
     * @return list<string> `i18nAttributes` plus the translatable custom attributes of the model's current state
     */
    public function getI18nAttributes(): array
    {
        return $this instanceof CustomAttributeInterface
            ? [...array_values($this->i18nAttributes), ...$this->getTranslatableCustomAttributeNames()]
            : array_values($this->i18nAttributes);
    }

    public function getI18nAttribute(string $attribute, ?string $language = null, bool $fallback = false): mixed
    {
        $attribute = $this->getI18nAttributeName($attribute, $language, $fallback);
        return $this->$attribute;
    }

    public function getI18nAttributeName(string $attribute, ?string $language = null, bool $fallback = false): string
    {
        if (!$this->isI18nAttribute($attribute)) {
            return $attribute;
        }

        $name = Yii::$app->getI18n()->getAttributeName($attribute, $language);

        return $fallback && $name !== $attribute && ($this->$name === null || $this->$name === '')
            ? $attribute
            : $name;
    }

    public function getI18nAttributeNames(string $attribute, ?array $languages = null): array
    {
        if (!$this->isI18nAttribute($attribute)) {
            return [Yii::$app->language => $attribute];
        }

        $i18n = Yii::$app->getI18n();
        $names = [];

        if (!$languages) {
            $languages = $i18n->getLanguages();
        }

        foreach ($languages as $language) {
            $names[$language] = $i18n->getAttributeName($attribute, $language);
        }

        return $names;
    }

    public function getI18nAttributesNames(array|string $attributes, ?array $languages = null): array
    {
        $i18n = Yii::$app->getI18n();
        $names = [];

        foreach ((array)$attributes as $attribute) {
            $names = [...$names, ...$this->isI18nAttribute($attribute)
                ? $i18n->getAttributeNames($attribute, $languages)
                : [$attribute]];
        }

        return $names;
    }

    public function getAttributeHint($attribute): string
    {
        return $this->getI18nHints()[$attribute] ?? parent::getAttributeHint($attribute);
    }

    public function getAttributeLabel($attribute): string
    {
        $labels = $this->getI18nLabels();

        return $labels[$attribute] ?? parent::getAttributeLabel($attribute);
    }

    public function getI18nHints(): array
    {
        $this->buildI18nLabelsAndHints();
        return $this->_i18nHints;
    }

    protected function getI18nLabels(): array
    {
        $this->buildI18nLabelsAndHints();
        return $this->_i18nLabels;
    }

    /**
     * Keyed rather than built once: the attributes of a model with custom attributes change with its state.
     */
    private function buildI18nLabelsAndHints(): void
    {
        $attributes = $this->getI18nAttributes();

        if ($this->_i18nLabels !== null && $this->_i18nLabelsKey === $attributes) {
            return;
        }

        $i18n = Yii::$app->getI18n();

        $this->_i18nLabelsKey = $attributes;
        $this->_i18nLabels = [];
        $this->_i18nHints = $this->attributeHints();

        foreach ($attributes as $attribute) {
            foreach ($i18n->getLanguages() as $language) {
                $name = $i18n->getAttributeName($attribute, $language);
                $label = parent::getAttributeLabel($attribute);

                if ($language !== Yii::$app->language) {
                    $label = Yii::t('skeleton', 'I18N_ATTRIBUTES_LABEL_LANGUAGE', [
                        'label' => $label,
                        'language' => strtoupper((string)$language),
                    ]);
                }

                $this->_i18nLabels[$name] = $label;
                $this->_i18nHints[$name] ??= $this->_i18nHints[$attribute] ?? null;
            }
        }
    }

    public function getI18nRules(array $rules): array
    {
        if ($this->getI18nAttributes()) {
            foreach ($rules as $key => $rule) {
                if ($this->isUniqueRule($rule[1])) {
                    $attribute = is_array($rule[0]) ? array_pop($rule[0]) : $rule[0];

                    foreach ($this->getI18nAttributeNames($attribute) as $language => $i18nAttribute) {
                        if ($attribute !== $i18nAttribute) {
                            $i18nRule = $rule;
                            $i18nRule[0] = $i18nAttribute;

                            if (in_array($rule[1], ['unique', BaseUniqueValidator::class], true)) {
                                $i18nRule[1] = UniqueValidator::class;
                            }

                            $targetAttribute = (array)($i18nRule['targetAttribute'] ?? $attribute);
                            $i18nRule['targetAttribute'] = $this->getI18nAttributesNames($targetAttribute, [$language]);

                            $rules[] = $i18nRule;
                        }
                    }

                    continue;
                }

                $rules[$key][0] = $this->getI18nAttributesNames($rule[0]);
            }
        }

        return $rules;
    }

    /**
     * If an i18n attribute has a unique validator with a "targetAttribute", all related attributes need their own rule
     * translating the target attribute.
     *
     * Override this method if a custom unique validator is used. It is kept on the per-language rules and has to handle
     * a translated target attribute itself.
     */
    protected function isUniqueRule(mixed $ruleName): bool
    {
        return in_array($ruleName, ['unique', BaseUniqueValidator::class, UniqueValidator::class], true);
    }

    public function isI18nAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getI18nAttributes(), true);
    }
}
