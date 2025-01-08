<?php

namespace davidhirtz\yii2\skeleton\html;

use Stringable;
use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Base\Tag;
use Yiisoft\Html\Tag\CustomTag;
use Yiisoft\Html\Tag\Li;
use Yiisoft\Html\Tag\Ul;

class Dropdown extends NormalTag
{
    protected array $attributes = ['class' => 'dropdown'];
    private Button $button;
    private array $items = [];

    public function button(string|Stringable $content): self
    {
        $this->button = ($content instanceof Button
            ? $content
            : Button::tag()
                ->class('btn dropdown-toggle')
                ->content($content))
            ->attribute('data-dropdown', '');

        return $this;
    }

    public function dropend(): self
    {
        return $this->addClass('dropdown-menu-end');
    }

    public function dropup(): self
    {
        return $this->addClass('dropup');
    }

    public function item(Tag $item): self
    {
        $new = clone $this;
        $new->items[] = Li::tag()->content($item->addClass('dropdown-item'));
        return $new;
    }

    public function items(Tag ...$items): self
    {
        $new = clone $this;
        $new->items = [];

        foreach ($items as $item) {
            $new = $new->item($item);
        }

        return $new;
    }

    protected function generateContent(): string
    {
        return $this->button->render() . CustomTag::name('dialog')
                ->class('dropdown-menu')
                ->content(Ul::tag()
                    ->items(...$this->items));
    }

    protected function getName(): string
    {
        return 'div';
    }
}
