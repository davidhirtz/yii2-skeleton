<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\H1;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Header extends Widget
{
    use TagAttributesTrait;
    use TagTitleTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->title
            ? H1::make()
                ->content($this->title)
                ->attributes($this->attributes)
                ->addClass('page-header')
            : '';
    }
}