<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\I18nActiveQuery;
use Yii;

/**
 * @template TActiveRecord
 * @property class-string<TActiveRecord> $modelClass
 *
 * @property array $i18nAttributes
 * @mixin ActiveRecord
 */
trait I18nAttributesTrait
{
    /**
     * @var array containing the attribute names of attributes which should be used with I18N features
     */
    public array $i18nAttributes = [];

    private ?array $_i18nHints = null;
    private ?array $_i18nLabels = null;

    /**
     * @return ActiveQuery<static>
     */
    public static function find(): ActiveQuery
    {
        return Yii::createObject(I18nActiveQuery::class, [static::class]);
    }

    public function getI18nAttribute(string $attribute, ?string $language = null): mixed
    {
        if ($this->isI18nAttribute($attribute)) {
            $attribute = $this->getI18nAttributeName($attribute, $language);
        }

        return $this->$attribute;
    }

    /**
     * Returns the translated attribute name for given language. If language is omitted, the current application
     * language is used.
     */
    public function getI18nAttributeName(string $attribute, ?string $language = null): string
    {
        return $this->isI18nAttribute($attribute)
            ? Yii::$app->getI18n()->getAttributeName($attribute, $language)
            : $attribute;
    }

    /**
     * Returns an array with all attribute variations indexed by language. If the attribute is not translated, this
     * method returns the attribute indexed by the current application language.
     */
    public function getI18nAttributeNames(string $attribute, ?string $languages = null): array
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

    /**
     * Returns a flat array with all translated attribute names for given languages. If the languages are omitted, all
     * available languages are used.
     */
    public function getI18nAttributesNames(array|string $attributes, ?array $languages = null): array
    {
        $i18n = Yii::$app->getI18n();
        $names = [];

        foreach ((array)$attributes as $attribute) {
            $names = array_merge($names, $this->isI18nAttribute($attribute)
                ? $i18n->getAttributeNames($attribute, $languages)
                : [$attribute]);
        }

        return $names;
    }

    public function getAttributeHint($attribute): string
    {
        return $this->getI18nHints()[$attribute] ?? parent::getAttributeHint($attribute);
    }

    public function getAttributeLabel($attribute): string
    {
        if ($this->i18nAttributes) {
            $labels = $this->getI18nLabels();

            if (isset($labels[$attribute])) {
                return $labels[$attribute];
            }
        }

        return parent::getAttributeLabel($attribute);
    }

    public function getI18nHints(): array
    {
        if ($this->_i18nHints === null) {
            $i18n = Yii::$app->getI18n();
            $this->_i18nHints = $this->attributeHints();

            foreach ($this->i18nAttributes as $attribute) {
                foreach ($i18n->getLanguages() as $language) {
                    $this->_i18nHints[$i18n->getAttributeName($attribute, $language)] ??= $this->_i18nHints[$attribute] ?? null;
                }
            }
        }

        return $this->_i18nHints;
    }

    public function getI18nLabels(): array
    {
        if ($this->_i18nLabels === null) {
            $i18n = Yii::$app->getI18n();
            $this->_i18nLabels = [];

            foreach ($this->i18nAttributes as $attribute) {
                foreach ($i18n->getLanguages() as $language) {
                    $label = parent::getAttributeLabel($attribute);

                    if ($language != Yii::$app->language) {
                        $label = Yii::t('skeleton', '{label} ({language})', [
                            'label' => $label,
                            'language' => strtoupper((string)$language),
                        ]);
                    }

                    $this->_i18nLabels[$i18n->getAttributeName($attribute, $language)] = $label;
                }
            }
        }

        return $this->_i18nLabels;
    }

    public function getI18nRules(array $rules): array
    {
        if ($this->i18nAttributes) {
            foreach ($rules as $key => $rule) {
                if ($this->isUniqueRule($rule[1])) {
                    $attribute = is_array($rule[0]) ? array_pop($rule[0]) : $rule[0];

                    foreach ($this->getI18nAttributeNames($attribute) as $language => $i18nAttribute) {
                        if ($attribute !== $i18nAttribute) {
                            $i18nRule = $rule;
                            $i18nRule[0] = $i18nAttribute;

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
     * Override this method if a custom unique validator is used.
     */
    protected function isUniqueRule(string $ruleName): bool
    {
        return $ruleName === 'unique';
    }

    public function isI18nAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->i18nAttributes);
    }
}
