<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Forms\Traits\InputGroupTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class InputGroup extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use InputGroupTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $div = Div::make()
            ->attributes($this->attributes)
            ->addClass('input-group');

        foreach ($this->prepend as $content) {
            $div->addContent($this->getAddon($content, 'input-group-prepend'));
        }

        $div->addContent(...$this->content);

        foreach ($this->append as $content) {
            $div->addContent($this->getAddon($content, 'input-group-append'));
        }

        return $div;
    }

    /**
     * Each one is a cell of its own, so two buttons appended read as two, divided the way the addon beside the
     * input is.
     */
    protected function getAddon(string|Stringable $content, string $class): Div
    {
        return ($content instanceof Div ? $content : Div::make()->content($content))->addClass($class);
    }
}
