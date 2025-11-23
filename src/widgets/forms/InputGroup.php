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

        if ([] !== $this->prepend) {
            $div->content(
                Div::make()
                    ->addClass('input-group-prepend')
                    ->addContent(...$this->prepend)
            );
        }

        $div->addContent(...$this->content);

        if ([] !== $this->append) {
            $div->addContent(
                Div::make()
                    ->addClass('input-group-append')
                    ->addContent(...$this->append)
            );
        }

        return $div;
    }
}
