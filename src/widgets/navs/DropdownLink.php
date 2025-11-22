<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;

class DropdownLink extends A
{
    #[\Override]
    protected function before(): string
    {
        $this->addClass('dropdown-link');
        return parent::before();
    }
}
