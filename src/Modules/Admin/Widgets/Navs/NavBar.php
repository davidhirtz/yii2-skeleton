<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Html\Header;
use Hirtz\Skeleton\Widgets\Widget;
use Yii;

class NavBar extends Widget
{
    public array $attributes = ['class' => 'navbar'];

    protected function renderContent(): Header
    {
        $container = Container::make()
            ->addClass('navbar-container')
            ->addContent($this->getMobileToggle())
            ->addContent($this->getAccountMenu());

        return Header::make()
            ->attributes($this->attributes)
            ->content($container);
    }

    protected function getAccountMenu(): AccountMenu
    {
        return AccountMenu::make();
    }

    protected function getMobileToggle(): string
    {
        return Button::make()
            ->class('aside-toggler')
            ->attribute('onclick', "body.classList.toggle('has-aside')")
            ->attribute('aria-label', Yii::t('skeleton', 'Toggle navigation'))
            ->render();
    }
}
