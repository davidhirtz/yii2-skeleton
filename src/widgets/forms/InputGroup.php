<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\InputGroupTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
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
