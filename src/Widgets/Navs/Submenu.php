<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

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

    public function __construct(array $config = [])
    {
        $this->webuser = Yii::$app->getUser();
        parent::__construct($config);
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getContent();
    }

    protected function getContent(): string|Stringable
    {
        return $this->items
            ? Container::make()->content($this->getNav())
            : '';
    }

    protected function getNav(): Stringable
    {
        return Nav::make()
            ->attributes($this->navAttributes)
            ->items(...$this->items);
    }
}
