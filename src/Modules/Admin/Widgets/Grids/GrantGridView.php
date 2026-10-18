<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Modules\Admin\Controllers\GrantController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
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
                Div::make()->text($grant->client_name ?? $grant->getClientHost()),
                Span::make()->class('small')->text($grant->getClientHost()),
            ));
    }

    protected function getScopeColumn(): ?Column
    {
        return DataColumn::make()
            ->property('scope')
            ->content(fn (OAuthGrant $grant): string => $grant->isReadOnly()
                ? Yii::t('skeleton', 'GRANT_SCOPE_READ')
                : Yii::t('skeleton', 'GRANT_SCOPE_WRITE'));
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
                    ->label(Yii::t('skeleton', 'GRANT_BUTTON_REVOKE'))
                    ->title(Yii::t('skeleton', 'GRANT_CONFIRM_REVOKE'))
                    ->url(['/admin/grant/revoke', 'id' => $grant->id]),
            ]);
    }
}
