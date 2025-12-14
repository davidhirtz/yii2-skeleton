<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagIconTrait;
use Hirtz\Skeleton\Html\Traits\TagLabelTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Html\Traits\TagVisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
