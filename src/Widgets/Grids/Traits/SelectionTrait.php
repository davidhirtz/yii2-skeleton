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
 * The checkbox column, the footer the selection reveals and the delete and status buttons in it. A using class calls
 * {@see static::configureSelection()} from its own `configure()` before it builds its columns, since a trait
 * has nowhere to register a listener, and names the checkbox column itself — where it sits is the grid's own
 * business.
 *
 * @phpstan-require-extends GridView
 */
trait SelectionTrait
{
    public bool $showSelection = true;

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
     * Whether the acting user may delete what the grid lists at all — a picker, a grid of somebody else's
     * records or one the permission does not cover offers no selection rather than a button that 403s.
     */
    protected function canDeleteSelection(): bool
    {
        return true;
    }

    /**
     * Whether the acting user may change the status of what the grid lists; opt-in, and the button also needs
     * {@see static::getUpdateSelectionRoute()}. Without this or {@see static::canDeleteSelection()} a grid offers no
     * selection at all, whatever else it adds to it.
     */
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

    /**
     * One item per status of the listed model, each posting it with the checked rows to the update route.
     */
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
                ->text(Yii::t('skeleton', 'COMMON_STATUS_SELECTED'))
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
        return Yii::t('skeleton', 'COMMON_CONFIRM_DELETE_SELECTED');
    }

    protected function getDeleteSelectionLabel(): string
    {
        return '';
    }

    /**
     * @return array<int|string, mixed>|null `null` for a grid without a bulk delete
     */
    protected function getDeleteSelectionRoute(): ?array
    {
        return null;
    }

    /**
     * @return array<int|string, mixed>|null the action receiving the status and the selection, `null` for none
     */
    protected function getUpdateSelectionRoute(): ?array
    {
        return null;
    }
}
