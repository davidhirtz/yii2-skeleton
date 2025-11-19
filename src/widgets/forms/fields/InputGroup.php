<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Override;
use Stringable;

class InputGroup extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    protected ?array $append = null;
    protected ?array $prepend = null;

    public function append(string|Stringable|null ...$content): static
    {
        $this->append = $content;
        return $this;
    }

    public function prepend(string|Stringable|null ...$content): static
    {
        $this->prepend = $content;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $div = Div::make()
            ->addClass('input-group');

        if (null !== $this->prepend) {
            $div->content(
                Div::make()
                    ->addClass('input-group-prepend')
                    ->addContent(...$this->prepend)
            );
        }

        $div->addContent(...$this->content);

        if (null !== $this->append) {
            $div->addContent(
                Div::make()
                    ->addClass('input-group-append')
                    ->addContent(...$this->append)
            );
        }

        return $div;
    }
}