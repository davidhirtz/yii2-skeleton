<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;

trait ContainerWidgetTrait
{
    use TagAttributesTrait;
    use TagIdTrait;

    public function render(): string
    {
        $html = parent::render();

        return $html
            ? Container::make()
                ->addAttributes($this->attributes)
                ->content($html)
                ->render()
            : '';
    }
}
