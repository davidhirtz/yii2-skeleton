<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Closure;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Submenu extends Widget
{
    /** @use ItemTrait<NavItem> */
    use ItemTrait;

    use TagContentTrait;
    use TitleTrait;
    use UrlTrait;

    protected array $navAttributes = ['class' => 'tabs'];
    protected User $webuser;

    protected Closure|Header|null $header = null;

    public function __construct(array $config = [])
    {
        $this->webuser = Yii::$app->getUser();
        parent::__construct($config);
    }

    /**
     * @param Closure(Header):Header|Header|null $header
     */
    public function header(Closure|Header|null $header): static
    {
        $this->header = $header;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getHeader() . $this->getNav();
    }

    protected function getHeader(): ?Stringable
    {
        $header = $this->header instanceof Header
            ? $this->header
            : Header::make()
                ->title($this->title)
                ->url($this->url);

        return $this->header instanceof Closure ? ($this->header)($header) : $header;
    }

    protected function getNav(): ?Stringable
    {
        return $this->items
            ? Container::make()
                ->content(Nav::make()
                    ->attributes($this->navAttributes)
                    ->items($this->items))
            : null;
    }
}
