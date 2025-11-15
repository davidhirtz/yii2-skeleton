<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\H1;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Header extends Widget
{
    use TagAttributesTrait;
    use TagTitleTrait;
    use TagUrlTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->title
            ? H1::make()
                ->attributes($this->attributes)
                ->addClass('page-header')
                ->content($this->url
                    ? A::make()->content($this->title)
                        ->href($this->url)
                    : $this->title)
            : '';
    }
}
