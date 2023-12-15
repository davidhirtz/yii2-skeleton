<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;

class I18nActiveQuery extends ActiveQuery
{
    public function getI18nAttributeName(string $attribute, ?string $language = null): string
    {
        $instance = $this->getModelInstance();
        $attribute = method_exists($instance, 'getI18nAttributeName')
            ? $instance->getI18nAttributeName($attribute, $language)
            : $attribute;

        return "{$this->getTableAlias()}.[[$attribute]]";
    }

    public function replaceI18nAttributes(): static
    {
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            if (is_array($this->select)) {
                $instance = $this->getModelInstance();
                $attributes = property_exists($instance, 'i18nAttributes')
                    ? $instance->i18nAttributes
                    : [];

                if ($attributes) {
                    $attributes = array_combine($attributes, $this->prefixColumns($attributes));
                    $alias = $this->getTableAlias();
                    $i18n = Yii::$app->getI18n();

                    foreach ($this->select as $key => $column) {
                        $attribute = isset($attributes[$column]) ? $column : array_search($column, $attributes);

                        if ($attribute) {
                            $column = "$alias.[[" . $i18n->getAttributeName($attribute) . ']]';
                            $this->select[$column] = $column;
                            unset($this->select[$key]);
                        }
                    }

                    $this->select = array_unique($this->select);
                }
            }
        }

        return $this;
    }
}