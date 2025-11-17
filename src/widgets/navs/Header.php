<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\H1;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Header extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagTitleTrait;
    use TagUrlTrait;

    protected function renderContent(): string
    {
        return $this->getTitle() . $this->getContent();
    }

    protected function getTitle(): Stringable
    {
        $content = $this->url
            ? A::make()
                ->text($this->title)
                ->href($this->url)
            : $this->title;

        return H1::make()
            ->attributes($this->attributes)
            ->addClass('page-header')
            ->content($content);
    }

    protected function getContent(): ?Stringable
    {
        return $this->content
            ? Div::make()
                ->class('small')
                ->content(...$this->content)
            : null;
    }
}
