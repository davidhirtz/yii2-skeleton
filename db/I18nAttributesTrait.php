<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;

/**
 * Class I18nAttributesTrait.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property array $i18nAttributes
 */
trait I18nAttributesTrait
{
    /**
     * @see getI18nLabels()
     * @var array
     */
    private $_i18nLabels;

    /**
     * @param string $attribute
     * @param string|null $language
     * @return mixed
     */
    public function getI18nAttribute(string $attribute, $language = null)
    {
        return $this->getAttribute($this->isI18nAttribute($attribute) ? $this->getI18nAttributeName($attribute, $language) : $attribute);
    }

    /**
     * Returns the translated attribute name for given language. If language is omitted
     * the current application language is used.
     *
     * @param string $attribute
     * @param string|null $language
     * @return string
     */
    public function getI18nAttributeName(string $attribute, $language = null)
    {
        return $this->isI18nAttribute($attribute) ? Yii::$app->getI18n()->getAttributeName($attribute, $language) : $attribute;
    }

    /**
     * Returns an array with all attribute variations indexed by language. If the attribute
     * is not translated this method returns the attribute indexed by the current application
     * language.
     *
     * @param string $attribute
     * @param array|null $languages
     * @return array
     */
    public function getI18nAttributeNames(string $attribute, $languages = null)
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
     * Returns a flat array with all translated attribute name for given languages.
     * If languages is omitted all available languages are used.
     *
     * @param array|string $attributes
     * @param array|null $languages
     * @return array
     */
    public function getI18nAttributesNames($attributes, $languages = null)
    {
        $i18n = Yii::$app->getI18n();
        $names = [];

        foreach ((array)$attributes as $attribute) {
            $names = array_merge($names, $this->isI18nAttribute($attribute) ? $i18n->getAttributeNames($attribute, $languages) : [$attribute]);
        }

        return $names;
    }

    /**
     * @param string $attribute
     * @return null
     */
    public function getAttributeLabel($attribute)
    {
        if ($this->i18nAttributes) {
            $labels = $this->getI18nLabels();

            if (isset($labels[$attribute])) {
                return $labels[$attribute];
            }
        }

        /** @noinspection PhpUndefinedClassInspection */
        return parent::getAttributeLabel($attribute);
    }

    /**
     * @return array|null
     */
    public function getI18nLabels()
    {
        if ($this->_i18nLabels === null) {
            $this->_i18nLabels = [];
            $i18N = Yii::$app->getI18n();

            foreach ($this->i18nAttributes as $attribute) {
                foreach ($i18N->getLanguages() as $language) {
                    /** @noinspection PhpUndefinedClassInspection */
                    $label = parent::getAttributeLabel($attribute);

                    $this->_i18nLabels[Yii::$app->getI18n()->getAttributeName($attribute, $language)] = $language == Yii::$app->language ? $label : Yii::t('skeleton', '{label} ({language})', [
                        'label' => $label,
                        'language' => strtoupper($language),
                    ]);
                }
            }
        }

        return $this->_i18nLabels;
    }

    /**
     * @param array $rules
     * @return mixed
     */
    public function getI18nRules(array $rules)
    {
        if ($this->i18nAttributes) {
            foreach ($rules as $key => $rule) {
                // If an i18n attribute has an unique validator with a targetAttribute all related
                // attributes need their own rule translating the target attribute.
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

    /**
     * @param string $attribute
     * @return bool
     */
    public function isI18nAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->i18nAttributes);
    }
}