<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\validators\RegularExpressionValidator;

/**
 * Class HexColorValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class HexColorValidator extends RegularExpressionValidator
{
    /**
     * @var string
     */
    public $pattern = '/^(?:[0-9a-fA-F]{3}){1,2}$/i';
}