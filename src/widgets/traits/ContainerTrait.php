<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use Stringable;

trait ContainerTrait
{
    use TagIdTrait;

    public function render(): string|Stringable
    {
        $html = parent::render();

        return $html
            ? Container::make()
                ->addAttributes($this->attributes)
                ->content($html)
            : '';
    }
}
