<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use Yii;

/**
 * @property array $i18nAttributes
 */
trait I18nAttributesTrait
{
    private ?array $_i18nHints = null;
    private ?array $_i18nLabels = null;

    public function getI18nAttribute(string $attribute, ?string $language = null): mixed
    {
        return $this->getAttribute($this->isI18nAttribute($attribute) ? $this->getI18nAttributeName($attribute, $language) : $attribute);
    }

    /**
     * Returns the translated attribute name for given language. If language is omitted, the current application
     * language is used.
     */
    public function getI18nAttributeName(string $attribute, ?string $language = null): string
    {
        return $this->isI18nAttribute($attribute) ? Yii::$app->getI18n()->getAttributeName($attribute, $language) : $attribute;
    }

    /**
     * Returns an array with all attribute variations indexed by language. If the attribute is not translated, this
     * method returns the attribute indexed by the current application language.
     */
    public function getI18nAttributeNames(string $attribute, ?string $languages = null): array
    {
        if ($this->isI18nAttribute($attribute)) {
            $i18n = Yii::$app->getI18n();
            $names = [];

            if (!$languages) {
                $languages = $i18n->getLanguages();
            }

            foreach ($languages as $language) {
                $names[$language] = $i18n->getAttributeName($attribute, $language);
            }
        } else {
            $names[Yii::$app->language] = $attribute;
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
            $names = array_merge($names, $this->isI18nAttribute($attribute) ? $i18n->getAttributeNames($attribute, $languages) : [$attribute]);
        }

        return $names;
    }

    public function getAttributeHint($attribute): string
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
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

        /** @noinspection PhpMultipleClassDeclarationsInspection */
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
                    /** @noinspection PhpMultipleClassDeclarationsInspection */
                    $label = parent::getAttributeLabel($attribute);

                    if ($language != Yii::$app->language) {
                        $label = Yii::t('skeleton', '{label} ({language})', [
                            'label' => $label,
                            'language' => strtoupper((string) $language),
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
                // If an i18n attribute has a unique validator with a "targetAttribute", all related attributes need
                // their own rule translating the target attribute.
                if ($rule[1] === 'unique' && !empty($rule['targetAttribute'])) {
                    $attribute = is_array($rule[0]) ? array_pop($rule[0]) : $rule[0];
                    foreach ($this->getI18nAttributesNames($attribute) as $i18nAttribute) {
                        if ($attribute !== $i18nAttribute) {
                            $i18nRule = $rule;
                            $i18nRule[0] = $i18nAttribute;
                            $i18nRule['targetAttribute'] = (array)$i18nRule['targetAttribute'];

                            foreach ((array)$i18nRule['targetAttribute'] as $targetKey => $targetAttribute) {
                                if ($targetAttribute === $attribute) {
                                    $i18nRule['targetAttribute'][$targetKey] = $i18nAttribute;
                                }
                            }

                            $rules[] = $i18nRule;
                        }
                    }
                } else {
                    $attributes = [];

                    foreach ((array)$rule[0] as $attribute) {
                        if ($attribute) {
                            foreach ($this->getI18nAttributesNames($attribute) as $i18nAttribute) {
                                $attributes[] = $i18nAttribute;
                            }
                        }
                    }

                    $rules[$key][0] = $attributes;
                }
            }
        }

        return $rules;
    }

    public function isI18nAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->i18nAttributes);
    }
}