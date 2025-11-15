<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use davidhirtz\yii2\skeleton\html\Container;

trait ContainerTrait
{
    public function run(): string
    {
        $html = parent::run();

        return $html
            ? Container::make()
                ->html($html)
                ->render()
            : '';
    }
}
