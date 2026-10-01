<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\validators\NumberValidator;
use yii\web\JsExpression;

class CurrencyValidator extends NumberValidator
{
    private const string NO_BREAK_SPACES = "\u{00A0}\u{202F}";

    public ?string $currencyPattern = null;
    public ?string $decimalSeparator = null;
    public ?string $thousandSeparator = null;

    #[\Override]
    public function init(): void
    {
        if (!$this->currencyPattern) {
            $format = Yii::$app->getFormatter()->asCurrency(1000);

            if (preg_match('/^(.*)(1(.)000(.)00)(.*)$/u', (string)$format, $matches)) {
                $this->decimalSeparator = $matches[4];
                $this->thousandSeparator = $matches[3];

                [, $prefix, , $thousands, $decimal, $suffix] = array_map(
                    fn (string $value): string => preg_quote((string)preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value)),
                    $matches
                );

                // `fr` and `pt-PT` group by a (narrow) no-break space, which nobody types: any space stands in for it
                $space = '[\s' . self::NO_BREAK_SPACES . ']';
                $thousands = $thousands === '' ? $space : $thousands;

                $this->currencyPattern = "/^($prefix)?$space*(-?(?:\d{1,3}(?:$thousands\d{3})+|(?!$thousands)\d*(?!$thousands\d))(?:$decimal\d+)?)$space*($suffix)?$/iu";
            } else {
                throw new InvalidConfigException("Currency format \"$format\" could not be parsed.");
            }
        }

        $this->message ??= Yii::t('yii', '{attribute} is invalid.');

        parent::init();
    }

    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (preg_match($this->currencyPattern, (string)$value, $matches)) {
            $value = preg_replace('/[\s' . self::NO_BREAK_SPACES . ']/u', '', $matches[2]);
            $value = str_replace([$this->thousandSeparator, $this->decimalSeparator], ['', '.'], (string)$value);
            $model->$attribute = floatval($value);
        }

        parent::validateAttribute($model, $attribute);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getClientOptions($model, $attribute): array
    {
        return [...parent::getClientOptions($model, $attribute), 'pattern' => new JsExpression($this->currencyPattern)];
    }
}
