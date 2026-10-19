<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\I18n;

use Override;

class Formatter extends \yii\i18n\Formatter
{
    public $sizeFormatBase = 1000;

    /**
     * Without `$decimals`, a size below a megabyte is whole (`242 kB`) and anything larger has one decimal (`4.2 MB`).
     *
     * @param array<int, mixed> $options
     * @param array<int, mixed> $textOptions
     */
    #[Override]
    public function asShortSize($value, $decimals = null, $options = [], $textOptions = [])
    {
        if ($decimals === null && is_numeric($value)) {
            $decimals = abs((float)$value) < $this->sizeFormatBase ** 2 ? 0 : 1;
        }

        return parent::asShortSize($value, $decimals, $options, $textOptions);
    }
}
