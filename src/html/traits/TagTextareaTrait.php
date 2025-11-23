<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagTextareaTrait
{
    public function rows(int $rows): static
    {
        return $this->attribute('rows', $rows);
    }

    public function cols(int $cols): static
    {
        return $this->attribute('cols', $cols);
    }
}
