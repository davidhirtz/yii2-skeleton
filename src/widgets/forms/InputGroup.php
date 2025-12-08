<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms;

use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\widgets\forms\traits\InputGroupTrait;
use Hirtz\Skeleton\widgets\Widget;
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
