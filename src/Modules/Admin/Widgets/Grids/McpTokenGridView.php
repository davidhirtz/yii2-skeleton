<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Modules\Admin\Controllers\McpTokenController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<UserToken>
 */
class McpTokenGridView extends GridView
{
    /**
     * @var bool whether the tokens are the acting user's own, which only they create
     */
    protected bool $isOwn = true;

    public function isOwn(bool $isOwn): static
    {
        $this->isOwn = $isOwn;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->header ??= $this->isOwn ? [$this->getCreateButton()] : null;

        $this->columns ??= [
            $this->getScopeColumn(),
            $this->getExpiresAtColumn(),
            $this->getLastUsedAtColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getScopeColumn(): ?Column
    {
        return DataColumn::make()
            ->property('type')
            ->title(Yii::t('skeleton', 'GRANT_SCOPE_LABEL'))
            ->content(fn (UserToken $token): string => $token->type === UserToken::TYPE_MCP_READ
                ? Yii::t('skeleton', 'GRANT_SCOPE_READ')
                : Yii::t('skeleton', 'GRANT_SCOPE_WRITE'));
    }

    protected function getExpiresAtColumn(): ?Column
    {
        return DataColumn::make()
            ->property('expires_at')
            ->title(Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_AT_LABEL'))
            ->content(fn (UserToken $token): string => $token->expires_at
                ? Yii::$app->getFormatter()->asDate($token->expires_at)
                : Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_NEVER'));
    }

    protected function getLastUsedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('last_used_at')
            ->title(Yii::t('skeleton', 'MCP_TOKEN_LAST_USED_AT_LABEL'));
    }

    protected function getCreatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->title(Yii::t('skeleton', 'MCP_TOKEN_CREATED_AT_LABEL'))
            ->hiddenForSmallDevices();
    }

    /**
     * @see McpTokenController::actionCreate()
     */
    protected function getCreateButton(): Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('skeleton', 'MCP_TOKEN_BUTTON_NEW'))
            ->url(['/admin/mcp-token/create']);
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ?->emptyMessage(Yii::t('skeleton', 'MCP_TOKEN_GRID_EMPTY'))
            ->emptyLink(Yii::t('skeleton', 'MCP_TOKEN_GRID_EMPTY_LINK'), $this->isOwn ? ['/admin/mcp-token/create'] : null)
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    /**
     * @see McpTokenController::actionRevoke()
     */
    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content(fn (UserToken $token): array => [
                DeleteGridButton::make()
                    ->label(Yii::t('skeleton', 'GRANT_BUTTON_REVOKE'))
                    ->title(Yii::t('skeleton', 'MCP_TOKEN_CONFIRM_REVOKE'))
                    ->url(['/admin/mcp-token/revoke', 'id' => $token->id]),
            ]);
    }
}
