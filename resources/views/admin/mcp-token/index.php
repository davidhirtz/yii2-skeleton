<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\McpTokenController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $tokens
 * @var string|null $token
 * @var User $user
 */

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Pre;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\McpTokenGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'MCP_TOKENS_TITLE'));

echo AccountHeader::make()
    ->model($user)
    ->addContent(CreateButton::make()
        ->label(Yii::t('skeleton', 'MCP_TOKEN_BUTTON_NEW'))
        ->url(['/admin/mcp-token/create']));

echo AccountSubmenu::make();

if ($token) {
    echo Container::make()
        ->content(Alert::make()
            ->warning()
            ->content(
                P::make()->text(Yii::t('skeleton', 'MCP_TOKEN_SHOWN_ONCE')),
                Pre::make()->attribute('id', 'mcp-token')->text($token),
            ));
}

echo GridContainer::make()
    ->title(Yii::t('skeleton', 'MCP_TOKENS_TITLE'))
    ->grid(McpTokenGridView::make()
        ->provider($tokens));
