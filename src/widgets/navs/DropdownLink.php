<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;

class DropdownLink extends A
{
    protected function prepareAttributes(): void
    {
        $this->addClass('dropdown-link');
        parent::prepareAttributes();
    }
}
