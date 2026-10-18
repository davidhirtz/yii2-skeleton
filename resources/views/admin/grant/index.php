<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\GrantController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider|null $grants
 * @var ActiveDataProvider $tokens
 * @var string|null $token
 * @var list<int> $tokenLifetimes
 * @var int|null $defaultTokenLifetime
 * @var User $user
 */

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Checkbox;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Option;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Pre;
use Hirtz\Skeleton\Html\Select;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\GrantGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\McpTokenGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Panels\Card;
use yii\data\ActiveDataProvider;

echo AccountHeader::make()
    ->model($user);

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

if ($grants) {
    echo GridContainer::make()
        ->title(Yii::t('skeleton', 'GRANT_CONNECTED_APPS'))
        ->grid(GrantGridView::make()
            ->provider($grants));
}

echo GridContainer::make()
    ->title(Yii::t('skeleton', 'MCP_TOKENS_TITLE'))
    ->grid(McpTokenGridView::make()
        ->provider($tokens));

echo Container::make()
    ->content(Card::make()
        ->title(Yii::t('skeleton', 'MCP_TOKEN_CREATE_TITLE'))
        ->content(Form::make()
            ->action(['/admin/grant/create-token'])
            ->content(
                Div::make()
                    ->addClass('form-checkbox')
                    ->content(
                        Div::make()
                            ->addClass('checkbox')
                            ->content(Checkbox::make()
                                ->addClass('input')
                                ->attribute('id', 'mcp-token-read-only')
                                ->name('readOnly')
                                ->value('1')),
                        Label::make()
                            ->addClass('label')
                            ->for('mcp-token-read-only')
                            ->text(Yii::t('skeleton', 'GRANT_READ_ONLY_LABEL')),
                    ),
                Div::make()
                    ->addClass('input-group')
                    ->content(
                        Select::make()
                            ->addClass('input')
                            ->attribute('id', 'mcp-token-expires')
                            ->attribute('name', 'expires')
                            ->attribute('aria-label', Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_AT_LABEL'))
                            ->options(
                                Option::make()
                                    ->value('')
                                    ->label(Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_NEVER')),
                                ...array_map(fn (int $days): Option => Option::make()
                                    ->value($days)
                                    ->selected($days === $defaultTokenLifetime)
                                    ->label(Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_DAYS', ['days' => $days])), $tokenLifetimes),
                            ),
                        Button::make()
                            ->attributes(['type' => 'submit'])
                            ->addClass('btn btn-primary')
                            ->text(Yii::t('skeleton', 'MCP_TOKEN_BUTTON_CREATE')),
                    ),
            )));
