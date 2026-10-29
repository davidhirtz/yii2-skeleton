<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\UserCredential;
use Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Yii;

/**
 * @extends GridView<UserCredential>
 */
class CredentialGridView extends GridView
{
    protected bool $isOwn = true;

    public function isOwn(bool $isOwn): static
    {
        $this->isOwn = $isOwn;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [
            $this->getProviderColumn(),
            $this->getHintColumn(),
            $this->getLastUsedAtColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getProviderColumn(): ?Column
    {
        return DataColumn::make()
            ->property('provider')
            ->content(fn (UserCredential $credential): string => Credentials::getComponent()
                ->getProvider($credential->provider)?->getLabel() ?? $credential->provider);
    }

    protected function getHintColumn(): ?Column
    {
        return DataColumn::make()
            ->property('hint');
    }

    protected function getLastUsedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('last_used_at');
    }

    protected function getCreatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForSmallDevices();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ?->emptyMessage(Yii::t('skeleton', 'CREDENTIAL_GRID_EMPTY'))
            ->emptyLink(Yii::t('skeleton', 'CREDENTIAL_GRID_CREATE_TEXT'), $this->isOwn ? ['/admin/credential/create'] : null)
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    /**
     * @see CredentialController::actionDelete()
     */
    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content(fn (UserCredential $credential): array => [
                DeleteGridButton::make()
                    ->label(Yii::t('skeleton', 'CREDENTIAL_DELETE_BUTTON'))
                    ->title(Yii::t('skeleton', 'CREDENTIAL_DELETE_CONFIRM'))
                    ->url(['/admin/credential/delete', 'id' => $credential->id]),
            ]);
    }
}
