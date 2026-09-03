<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Override;

class TrimValidator extends \yii\validators\TrimValidator
{
    #[Override]
    protected function trimValue($value): string
    {
        return $this->isEmpty($value) ? '' : mb_trim((string)$value, $this->chars ?: " \n\r\t\v\x00");
    }
}
