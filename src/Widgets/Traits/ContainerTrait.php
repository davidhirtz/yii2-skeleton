<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Container;

trait ContainerTrait
{
    use TagAttributesTrait;
    use TagIdTrait;

    protected bool $container = true;

    /**
     * Without its container, the widget renders bare (for a column of `Container::columns()`), and its attributes,
     * which belong to the container, are not rendered.
     */
    public function container(bool $container = true): static
    {
        $this->container = $container;
        return $this;
    }

    public function render(bool $refresh = false): string
    {
        $html = parent::render($refresh);

        return $html && $this->container
            ? Container::make()
                ->addAttributes($this->attributes)
                ->content($html)
                ->render()
            : $html;
    }
}
