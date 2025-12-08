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
            ->addClass('input-group');

        if (null !== $this->prepend) {
            $div->content(($this->prepend instanceof Div ? $this->prepend : (Div::make()->content($this->prepend)))
                ->addClass('input-group-prepend'));
        }

        $div->addContent(...$this->content);

        if (null !== $this->append) {
            $div->addContent(($this->append instanceof Div ? $this->append : (Div::make()->content($this->append)))
                ->addClass('input-group-append'));
        }

        return $div;
    }
}
