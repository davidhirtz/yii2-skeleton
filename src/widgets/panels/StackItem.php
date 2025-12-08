<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\panels;

use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Li;
use Hirtz\Skeleton\html\Span;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagIconTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\html\traits\TagVisibilityTrait;
use Hirtz\Skeleton\widgets\Widget;
use Override;
use Stringable;

class StackItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;
    use TagLabelTrait;
    use TagUrlTrait;
    use TagVisibilityTrait;

    public array $linkAttributes = ['class' => 'stack-link'];

    #[Override]
    protected function renderContent(): Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass('stack-item')
            ->content($this->getContent());
    }

    public function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->attributes($this->linkAttributes)
            ->addClass('stack-item-link')
            ->href($this->url);

        if ($this->icon) {
            $link->addContent($this->icon);
        }

        if ($this->label) {
            $link->addContent(Span::make()
                ->text($this->label));
        }

        return $link;
    }
}
