<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Aside;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsidePinButton;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AsideMenu extends Widget
{
    /**
     * @var array<string, mixed>
     */
    public array $attributes = [
        'class' => 'aside',
        'id' => 'aside',
    ];

    protected ?string $mainMenu = null;
    protected ?string $accountMenu = null;

    /**
     * The menus are rendered here rather than in {@see renderContent()}, because {@see isVisible()} answers from
     * what they came to — an aside holding nothing is left out of the document instead of being hidden by a CSS
     * rule, which a theme adding a logo of its own would defeat.
     */
    #[Override]
    protected function configure(): void
    {
        $this->mainMenu ??= (string)$this->getMainMenu();
        $this->accountMenu ??= (string)$this->getAccountMenu();

        parent::configure();
    }

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && ($this->mainMenu || $this->accountMenu);
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Aside::make()
            ->attributes($this->attributes)
            ->content(...$this->getContent());
    }

    /**
     * @return list<string|Stringable|null>
     */
    protected function getContent(): array
    {
        return [$this->getHeader(), $this->mainMenu, $this->accountMenu];
    }

    protected function getHeader(): ?Stringable
    {
        return Div::make()
            ->class('aside-header')
            ->content($this->getPinButton());
    }

    protected function getPinButton(): Stringable
    {
        return AsidePinButton::make();
    }

    protected function getMainMenu(): Stringable
    {
        return MainMenu::make();
    }

    protected function getAccountMenu(): AccountMenu
    {
        return AccountMenu::make();
    }
}
