<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagIconTrait;
use Hirtz\Skeleton\Html\Traits\TagLabelTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Html\Traits\TagVisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class MainMenuItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;
    use TagLabelTrait;
    use TagUrlTrait;
    use TagVisibilityTrait;

    public array $linkAttributes = ['class' => 'nav-link'];

    protected function renderContent(): string|Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass()
            ->content(
                A::make()
                    ->attributes($this->linkAttributes)
                    ->icon($this->icon)
                    ->text($this->label)
                    ->href($this->url)
            );
    }
}
