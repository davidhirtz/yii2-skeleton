<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Modules\Admin\Controllers\GrantController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<OAuthGrant>
 */
class GrantGridView extends GridView
{
    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [
            $this->getClientColumn(),
            $this->getScopeColumn(),
            $this->getLastUsedAtColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getClientColumn(): ?Column
    {
        return DataColumn::make()
            ->property('client_name')
            ->content(fn (OAuthGrant $grant): Stringable => Div::make()->content(
                Div::make()->class('strong')->text($grant->client_name ?? $grant->getClientHost()),
                Span::make()->class('small')->text($grant->getClientHost()),
            ));
    }

    protected function getScopeColumn(): ?Column
    {
        return DataColumn::make()
            ->property('scope')
            ->content(fn (OAuthGrant $grant): string => $grant->isReadOnly()
                ? Yii::t('skeleton', 'GRANT_SCOPE_READ_NAME')
                : Yii::t('skeleton', 'GRANT_SCOPE_WRITE_NAME'));
    }

    protected function getLastUsedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('last_used_at')
            ->hiddenForSmallDevices();
    }

    protected function getCreatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForSmallDevices();
    }

    /**
     * @see GrantController::actionRevoke()
     */
    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content(fn (OAuthGrant $grant): array => [
                DeleteGridButton::make()
                    ->label(Yii::t('skeleton', 'GRANT_REVOKE_BUTTON'))
                    ->title(Yii::t('skeleton', 'GRANT_REVOKE_CONFIRM'))
                    ->url(['/admin/grant/revoke', 'id' => $grant->id]),
            ]);
    }

    /**
     * Names the address to connect with, which nothing else in the admin shows.
     */
    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ?->emptyMessage(Yii::t('skeleton', 'GRANT_GRID_EMPTY', [
                'url' => Mcp::getComponent()->getResourceUrl(),
            ]))
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }
}
