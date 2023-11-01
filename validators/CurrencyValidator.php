<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\base\InvalidConfigException;
use yii\validators\NumberValidator;
use Yii;
use yii\web\JsExpression;

/**
 * Class CurrencyValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class CurrencyValidator extends NumberValidator
{
    /**
     * @var string
     */
    public $currencyPattern;

    /**
     * @var string
     */
    public $decimalSeparator;

    /**
     * @var string
     */
    public $thousandSeparator;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->currencyPattern) {
            $format = Yii::$app->getFormatter()->asCurrency(1000);

            if (preg_match('/^(.*)(1(.)000(.)00)(.*)$/u', (string) $format, $matches)) {
                $this->decimalSeparator = $matches[4];
                $this->thousandSeparator = $matches[3];

                // Remove UTF-8 white spaces and quote for regular expression.
                $matches = array_map(fn($v): string => preg_quote(preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', (string) $v)), $matches);
                $this->currencyPattern = "/^({$matches[1]})?\s*(-?(?:\d{1,3}(?:{$matches[3]}\d{3})+|(?!{$matches[3]})\d*(?!{$matches[3]}))(?:{$matches[4]}[0-9]+)?)\s*({$matches[5]})?$/iu";
            } else {
                throw new InvalidConfigException("Currency format \"{$format}\" could not be parsed.");
            }
        }

        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }

        parent::init();
    }

    /**
     * Validates a currency and replaces it's value.
     *
     * @param \yii\base\Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (preg_match($this->currencyPattern, (string) $value, $matches)) {
            $value = str_replace([$this->thousandSeparator, $this->decimalSeparator], ['', '.'], $matches[2]);
            $model->$attribute = floatval($value);
        }

        parent::validateAttribute($model, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getClientOptions($model, $attribute)
    {
        return array_merge(parent::getClientOptions($model, $attribute), [
            'pattern' => new JsExpression($this->currencyPattern),
        ]);
    }
}