<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\traits;

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagIdTrait;

trait ContainerWidgetTrait
{
    use TagAttributesTrait;
    use TagIdTrait;

    public function render(bool $refresh = false): string
    {
        $html = parent::render($refresh);

        return $html
            ? Container::make()
                ->addAttributes($this->attributes)
                ->content($html)
                ->render()
            : '';
    }
}
