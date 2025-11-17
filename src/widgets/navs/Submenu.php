<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\navs\traits\NavItemTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Override;
use Stringable;

class Submenu extends Widget
{
    use ContainerWidgetTrait;
    use TagContentTrait;
    use TagTitleTrait;
    use TagUrlTrait;
    use NavItemTrait;

    public array $navAttributes = ['class' => 'submenu nav-pills'];
    public array $headerAttributes = [];

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getHeader() . $this->getNav();
    }

    protected function getHeader(): ?Header
    {
        return $this->title
            ? Header::make()
                ->attributes($this->headerAttributes)
                ->title($this->title)
                ->url($this->url)
            : null;
    }

    protected function getNav(): Nav
    {
        return Nav::make()
                ->attributes($this->navAttributes)
                ->items(...$this->items);
    }
}
