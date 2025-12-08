<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\navs;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;
use Yii;

class NavBar extends Widget
{
    public array $attributes = ['class' => 'navbar'];

    protected function renderContent(): string
    {
        $container = Container::make()
            ->addClass('navbar-container');

        $menu = (string)$this->getMainMenu();

        if ($menu) {
            $container->addContent($this->getMobileToggle());
            $container->addContent($menu);
        }

        $container->addContent($this->getAccountMenu());
        $content = $container->render();

        return $content ? "<header class=\"navbar\">$content</header>" : '';
    }

    protected function getMainMenu(): Stringable
    {
        return MainMenu::make();
    }

    protected function getAccountMenu(): AccountMenu
    {
        return AccountMenu::make();
    }

    protected function getMobileToggle(): string
    {
        return Button::make()
            ->class('navbar-toggler')
            ->attribute('data-collapse', "#menu")
            ->attribute('aria-label', Yii::t('skeleton', 'Toggle navigation'))
            ->render();
    }
}
