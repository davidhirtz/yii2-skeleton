<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Link;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class MainMenuItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use IconTrait;
    use LabelTrait;
    use UrlTrait;
    use VisibilityTrait;

    public array $linkAttributes = ['class' => 'nav-link'];

    protected function renderContent(): string|Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass()
            ->content(
                Link::make()
                    ->attributes($this->linkAttributes)
                    ->icon($this->icon)
                    ->text($this->label)
                    ->href($this->url)
            );
    }
}
