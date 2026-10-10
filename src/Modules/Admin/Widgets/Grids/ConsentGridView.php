<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;

/**
 * @extends GridView<Consent>
 */
class ConsentGridView extends GridView
{
    #[Override]
    protected function configure(): void
    {
        $this->header ??= [
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getUuidColumn(),
            $this->getCategoriesColumn(),
            $this->getVersionColumn(),
            $this->getCreatedAtColumn(),
        ];

        parent::configure();
    }

    protected function getUuidColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('uuid')
            ->url(fn (Consent $consent): array => ['index', 'q' => $consent->uuid]);
    }

    protected function getCategoriesColumn(): ?Column
    {
        return DataColumn::make()
            ->property('categories')
            ->value(fn (Consent $consent): string => implode(', ', $consent->categories));
    }

    protected function getVersionColumn(): ?Column
    {
        return DataColumn::make()
            ->property('version')
            ->hiddenForSmallDevices();
    }

    protected function getCreatedAtColumn(): ?Column
    {
        return DataColumn::make()
            ->property('created_at')
            ->format('datetime')
            ->hiddenForSmallDevices();
    }
}
