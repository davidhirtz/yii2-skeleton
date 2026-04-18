<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Override;

class ActionDropdown extends Dropdown
{
    use IconTrait;

    public function __construct(array $config = [])
    {
        $this->attributes['class'] ??= 'dropdown-actions';
        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'ellipsis-h';

        $this->button ??= Button::make()
            ->primary()
            ->icon($this->icon);

        parent::configure();
    }
}
