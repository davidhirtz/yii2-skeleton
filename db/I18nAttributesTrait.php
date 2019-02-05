<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\i18n\I18N;
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
     * @param string $language
     * @return mixed
     */
    public function getI18nAttribute($attribute, $language = null)
    {
        return $this->getAttribute(in_array($attribute, $this->i18nAttributes) ? $this->getI18nAttributeName($attribute, $language) : $attribute);
    }

    /**
     * @param string $attribute
     * @param string $language
     * @return string
     */
    public function getI18nAttributeName($attribute, $language = null)
    {
        return in_array($attribute, $this->i18nAttributes) ? I18N::getAttributeName($attribute, $language) : $attribute;
    }

    /**
     * @param string $attribute
     * @param string $language
     * @return array
     */
    public function getI18nAttributeNames($attribute, $language = null)
    {
        return in_array($attribute, $this->i18nAttributes) ? Yii::$app->getI18n()->getAttributeNames($attribute) : [$attribute];
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
                    $label = $this->getAttributeLabel($attribute);

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
    public function getI18nRules($rules)
    {
        if ($this->i18nAttributes) {
            foreach ($rules as $key => $rule) {
                $attributes = [];

                foreach ((array)$rule[0] as $attribute) {
                    if ($attribute) {
                        foreach ($this->getI18nAttributeNames($attribute) as $i18nAttribute) {
                            $attributes[] = $i18nAttribute;
                        }
                    }
                }

                $rules[$key][0] = $attributes;
            }
        }

        return $rules;
    }
}