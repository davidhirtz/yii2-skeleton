<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;

class Fieldset extends Tag
{
    use TagContentTrait;

    protected ?string $legend = null;

    public function legend(string|null $legend): static
    {
        $this->legend = $legend;
        return $this;
    }

    #[Override]
    protected function before(): string
    {
        if ($this->legend !== null) {
            array_unshift($this->content, Legend::make()
                ->text($this->legend));
        }

        return parent::before();
    }

    protected function getTagName(): string
    {
        return 'fieldset';
    }
}
