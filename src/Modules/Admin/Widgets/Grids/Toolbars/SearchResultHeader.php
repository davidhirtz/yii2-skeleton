<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridHeader;
use Override;
use Stringable;

class SearchResultHeader extends GridHeader
{
    #[Override]
    protected function configure(): void
    {
        $this->attributes['class'] ??= 'grid-header';
        parent::configure();
    }

    /**
     * Dropped when every filter hides itself, so an unfiltered page keeps no empty row.
     */
    #[Override]
    protected function renderContent(): string|Stringable
    {
        $content = array_values(array_filter(array_map(strval(...), $this->content)));

        return $content
            ? Div::make()
                ->attributes($this->attributes)
                ->content(...$content)
            : '';
    }
}
