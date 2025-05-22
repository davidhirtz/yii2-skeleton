<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;

class NavBar extends Widget
{
    /**
     * @var array the HTML attributes for the widget container tag.
     */
    public array $attributes = ['class' => 'navbar'];

    public function render(): string
    {
        $container = Container::make()
            ->addClass('navbar-container');

        if ($menu = $this->getMainMenu()) {
            $container->addHtml($this->getMobileToggle());
            $container->addHtml($menu);
        }

        $container->addHtml($this->getAccountMenu());

        $content = $container->render();

        return $content ? "<header class=\"navbar\">$content</header>" : '';
    }

    protected function getMainMenu(): string
    {
        return MainMenu::widget();
    }

    protected function getAccountMenu(): string
    {
        return AccountMenu::widget();
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
