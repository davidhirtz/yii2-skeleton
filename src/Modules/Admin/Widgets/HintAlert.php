<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * Explains a page whose model is not self-evident — a block, a permission, a redirect. Every one of them is
 * switched off at once by the account's `show_hints`, so a view renders it unconditionally and never asks.
 */
class HintAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'circle-info';

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Alert::make()
            ->attributes($this->attributes)
            ->content(...$this->content)
            ->info()
            ->icon($this->icon);
    }

    #[Override]
    public function isVisible(): bool
    {
        return $this->content !== []
            && ($this->webuser->getIdentity()?->showsHints() ?? false)
            && parent::isVisible();
    }
}
