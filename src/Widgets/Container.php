<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;
use Stringable;

class Container extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    public function centered(): static
    {
        return $this->addClass('container-centered');
    }

    #[Override]
    protected function configure(): void
    {
        $this->addClass('container');
        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Div::make()
            ->attributes($this->attributes)
            ->content(...$this->content);
    }
}
