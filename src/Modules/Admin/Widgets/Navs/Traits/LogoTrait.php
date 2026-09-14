<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs\Traits;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Img;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;
use Stringable;

trait LogoTrait
{
    use TagAttributesTrait;
    use TagContentTrait;

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && ($this->attributes || $this->content);
    }

    protected function getLogo(): ?Stringable
    {
        return array_key_exists('src', $this->attributes)
            ? Img::make()
                ->attributes($this->attributes)
            : Div::make()
                ->attributes($this->attributes)
                ->content(...$this->content);
    }

    protected function getLink(): ?Stringable
    {
        return A::make()
            ->href(!$this->webuser->getIsGuest() ? ['/admin/dashboard/index'] : null)
            ->content($this->getLogo());
    }
}
