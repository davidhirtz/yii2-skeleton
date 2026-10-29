<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\CheckboxColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridFooter;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Stringable;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * A using class calls {@see static::configureSelection()} from its own `configure()` before it builds its columns,
 * since a trait has nowhere to register a listener.
 *
 * @phpstan-require-extends GridView
 */
trait SelectionTrait
{
    protected bool $showSelection = true;

    public function showSelection(bool $showSelection = true): static
    {
        $this->showSelection = $showSelection;
        return $this;
    }

    protected function configureSelection(): void
    {
        $items = $this->showSelection && ($this->canDeleteSelection() || $this->canUpdateSelection())
            ? array_values(array_filter($this->getSelectionItems()))
            : [];

        $this->showSelection = $items !== [];

        if ($this->showSelection) {
            $this->footer ??= GridFooter::make()
                ->attributes($this->footerAttributes)
                ->addClass('hidden flex-has-selection')
                ->content(...$items);
        }
    }

    /**
     * A picker, a grid of somebody else's records or one the permission does not cover offers no selection rather than
     * a button that 403s.
     */
    protected function canDeleteSelection(): bool
    {
        return true;
    }

    /** Opt-in, and the button also needs {@see static::getUpdateSelectionRoute()}. */
    protected function canUpdateSelection(): bool
    {
        return false;
    }

    /**
     * @return list<Stringable|null>
     */
    protected function getSelectionItems(): array
    {
        return [$this->getStatusSelectionDropdown(), $this->getDeleteSelectionButton()];
    }

    protected function getStatusSelectionDropdown(): ?Stringable
    {
        $route = $this->getUpdateSelectionRoute();
        $model = $this->getSelectionModel();

        if ($route === null || !$model instanceof StatusAttributeInterface || !$this->canUpdateSelection()) {
            return null;
        }

        $dropdown = Dropdown::make()
            ->button(Button::make()
                ->primary()
                ->text(Yii::t('skeleton', 'GRID_STATUS_SELECTED_BUTTON'))
                ->icon('toggle-on'))
            ->dropup();

        foreach ($model::getStatusDefinitions() as $status) {
            $dropdown->addItem(Button::make()
                ->addClass('dropdown-option')
                ->type('button')
                ->text($status->getName())
                ->icon($status->getIcon())
                ->post($route)
                ->attribute('hx-include', '[data-check]:checked')
                ->attribute('hx-vals', (string)json_encode([$model->formName() . '[status]' => $status->value])));
        }

        return GridToolbarItem::make()
            ->content($dropdown);
    }

    protected function getCheckboxColumn(): ?Column
    {
        return $this->showSelection
            ? CheckboxColumn::make()
            : null;
    }

    protected function getSelectionModel(): ?Model
    {
        $query = $this->provider instanceof ActiveDataProvider ? $this->provider->query : null;
        $modelClass = $query instanceof ActiveQuery ? $query->modelClass : null;

        return $modelClass !== null && is_a($modelClass, Model::class, true) ? $modelClass::instance() : null;
    }

    protected function getDeleteSelectionButton(): ?Stringable
    {
        $route = $this->getDeleteSelectionRoute();

        if ($route === null || !$this->canDeleteSelection()) {
            return null;
        }

        return GridToolbarItem::make()
            ->content(ConfirmButton::make()
                ->danger()
                ->icon('trash')
                ->label($this->getDeleteSelectionLabel())
                ->text($this->getDeleteSelectionMessage())
                ->url($route)
                ->include('[data-check]:checked')
                ->pushHistory(false));
    }

    protected function getDeleteSelectionMessage(): string
    {
        return Yii::t('skeleton', 'GRID_DELETE_SELECTED_CONFIRM');
    }

    protected function getDeleteSelectionLabel(): string
    {
        return '';
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function getDeleteSelectionRoute(): ?array
    {
        return null;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function getUpdateSelectionRoute(): ?array
    {
        return null;
    }
}
