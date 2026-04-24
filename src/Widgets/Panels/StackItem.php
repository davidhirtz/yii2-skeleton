<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class StackItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use IconTrait;
    use LabelTrait;
    use UrlTrait;

    public array $linkAttributes = ['class' => 'stack-link'];

    #[Override]
    protected function renderContent(): Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass('stack-item')
            ->content($this->getContent());
    }

    protected function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->attributes($this->linkAttributes)
            ->addClass('stack-item-link')
            ->href($this->url);

        if ($this->icon) {
            $link->addContent($this->getIcon());
        }

        if ($this->label) {
            $link->addContent(Span::make()->text($this->label));
        }

        return $link;
    }
}
