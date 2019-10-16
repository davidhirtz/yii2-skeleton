<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\validators\RegularExpressionValidator;

/**
 * Class HexColorValidator.
 * @package davidhirtz\yii2\skeleton\validators
 */
class HexColorValidator extends RegularExpressionValidator
{
    /**
     * @var string
     */
    public $pattern = '/([a-f0-9]{3}){1,2}\b/i';
}