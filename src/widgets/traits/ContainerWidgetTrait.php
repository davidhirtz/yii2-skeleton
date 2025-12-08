<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;

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
