<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Traits;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\SelectionTrait;
use Override;
use yii\data\ArrayDataProvider;

class SelectionTraitTest extends TestCase
{
    public function testTheCheckboxColumnAndTheFooterRenderTogether(): void
    {
        $html = (string)$this->createGrid();

        self::assertStringContainsString('name="selection[]"', $html);
        self::assertStringContainsString('data-check-all="#selection-grid-view"', $html);

        self::assertStringContainsString('flex-has-selection', $html);
        self::assertStringContainsString('hx-post="/admin/redirect/delete-all"', $html);
        self::assertStringContainsString('hx-include="[data-check]:checked"', $html);
    }

    public function testASelectionTurnedOffRendersNeither(): void
    {
        $grid = $this->createGrid();
        $grid->showSelection = false;

        $html = (string)$grid;

        self::assertStringNotContainsString('name="selection[]"', $html);
        self::assertStringNotContainsString('flex-has-selection', $html);
    }

    /**
     * The permission is asked once for the whole grid, so a grid the user may not delete from offers no
     * checkbox rather than a button that answers 403.
     */
    public function testAGridThatRefusesTheSelectionRendersNeither(): void
    {
        $grid = $this->createGrid();
        $grid->canDelete = false;

        $html = (string)$grid;

        self::assertStringNotContainsString('name="selection[]"', $html);
        self::assertStringNotContainsString('flex-has-selection', $html);
    }

    private function createGrid(): SelectionTraitGridView
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'old';
        $redirect->url = '/new';

        return SelectionTraitGridView::make()
            ->provider(new ArrayDataProvider([
                'allModels' => [$redirect],
                'key' => 'request_uri',
            ]));
    }
}

/**
 * @extends GridView<Redirect>
 */
class SelectionTraitGridView extends GridView
{
    use SelectionTrait;

    public bool $canDelete = true;

    protected string $layout = '{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'selection-grid-view';

        $this->configureSelection();

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getRequestUriColumn(),
        ];

        parent::configure();
    }

    protected function canDeleteSelection(): bool
    {
        return $this->canDelete;
    }

    protected function getRequestUriColumn(): ?Column
    {
        return DataColumn::make()
            ->property('request_uri');
    }

    protected function getDeleteSelectionLabel(): string
    {
        return 'Delete selected';
    }

    protected function getDeleteSelectionRoute(): array
    {
        return ['/admin/redirect/delete-all'];
    }
}
