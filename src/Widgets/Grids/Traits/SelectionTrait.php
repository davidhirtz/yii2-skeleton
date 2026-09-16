<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\CheckboxColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridFooter;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Modal;
use Stringable;
use Yii;

/**
 * The checkbox column, the footer the selection reveals and the delete button in it. A using class calls
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
        $this->showSelection = $this->showSelection && $this->canDeleteSelection();

        if ($this->showSelection) {
            $this->footer ??= GridFooter::make()
                ->attributes($this->footerAttributes)
                ->addClass('hidden flex-has-selection')
                ->content(...$this->getSelectionItems());
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
     * @return list<Stringable|null>
     */
    protected function getSelectionItems(): array
    {
        return [$this->getDeleteSelectionButton()];
    }

    protected function getCheckboxColumn(): ?Column
    {
        return $this->showSelection
            ? CheckboxColumn::make()
            : null;
    }

    protected function getDeleteSelectionButton(): ?Stringable
    {
        $label = $this->getDeleteSelectionLabel();

        $modal = Modal::make()
            ->title($label)
            ->text($this->getDeleteSelectionMessage())
            ->footer(Button::make()
                ->danger()
                ->text($label)
                ->icon('trash')
                ->post($this->getDeleteSelectionRoute())
                ->attribute('hx-include', '[data-check]:checked'));

        return GridToolbarItem::make()
            ->content(Button::make()
                ->danger()
                ->text($label)
                ->icon('trash')
                ->modal($modal));
    }

    protected function getDeleteSelectionMessage(): string
    {
        return Yii::t('skeleton', 'COMMON_CONFIRM_DELETE_SELECTED');
    }

    abstract protected function getDeleteSelectionLabel(): string;

    /**
     * @return array<int|string, mixed>
     */
    abstract protected function getDeleteSelectionRoute(): array;
}
