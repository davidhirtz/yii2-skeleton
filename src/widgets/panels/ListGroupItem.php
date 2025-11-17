<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\html\Span;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Override;
use Stringable;

class ListGroupItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;
    use TagLabelTrait;
    use TagUrlTrait;
    use TagVisibilityTrait;

    public array $linkAttributes = ['class' => 'list-group-link'];

    #[Override]
    protected function renderContent(): Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass('list-group-item')
            ->content($this->getContent());
    }

    public function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->attributes($this->linkAttributes)
            ->addClass('list-group-item-link')
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
