<?php

namespace davidhirtz\yii2\skeleton\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\validators\NumberValidator;
use yii\web\JsExpression;

class CurrencyValidator extends NumberValidator
{
    public ?string $currencyPattern = null;
    public ?string $decimalSeparator = null;
    public ?string $thousandSeparator = null;

    public function init(): void
    {
        if (!$this->currencyPattern) {
            $format = Yii::$app->getFormatter()->asCurrency(1000);

            if (preg_match('/^(.*)(1(.)000(.)00)(.*)$/u', (string)$format, $matches)) {
                $this->decimalSeparator = $matches[4];
                $this->thousandSeparator = $matches[3];

                // Remove UTF-8 whitespaces and quote for regular expression.
                $matches = array_map(fn ($v): string => preg_quote(preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', (string)$v)), $matches);
                $this->currencyPattern = "/^($matches[1])?\s*(-?(?:\d{1,3}(?:$matches[3]\d{3})+|(?!$matches[3])\d*(?!$matches[3]))(?:$matches[4][0-9]+)?)\s*($matches[5])?$/iu";
            } else {
                throw new InvalidConfigException("Currency format \"$format\" could not be parsed.");
            }
        }

        $this->message ??= Yii::t('yii', '{attribute} is invalid.');

        parent::init();
    }

    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (preg_match($this->currencyPattern, (string)$value, $matches)) {
            $value = str_replace([$this->thousandSeparator, $this->decimalSeparator], ['', '.'], $matches[2]);
            $model->$attribute = floatval($value);
        }

        parent::validateAttribute($model, $attribute);
    }

    public function getClientOptions($model, $attribute): array
    {
        return array_merge(parent::getClientOptions($model, $attribute), [
            'pattern' => new JsExpression($this->currencyPattern),
        ]);
    }
}
