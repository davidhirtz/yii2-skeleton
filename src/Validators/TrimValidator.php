<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\validators;

use Override;

class TrimValidator extends \yii\validators\TrimValidator
{
    #[Override]
    protected function trimValue($value): string
    {
        return $this->isEmpty($value) ? '' : mb_trim((string)$value, $this->chars ?: " \n\r\t\v\x00");
    }
}
