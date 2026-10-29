<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Aside;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AsideMenu extends Widget
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [
        'class' => 'aside',
        'id' => 'aside',
    ];

    protected ?string $mainMenu = null;
    protected ?string $accountMenu = null;

    public function mainMenu(?string $mainMenu): static
    {
        $this->mainMenu = $mainMenu;
        return $this;
    }

    public function accountMenu(?string $accountMenu): static
    {
        $this->accountMenu = $accountMenu;
        return $this;
    }

    /**
     * Rendered here rather than in {@see renderContent()}, since {@see isVisible()} answers from what they came to.
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

    /**
     * The extension point for a theme's own logo, as `Hirtz\Anakin` renders.
     */
    protected function getHeader(): ?Stringable
    {
        return null;
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
