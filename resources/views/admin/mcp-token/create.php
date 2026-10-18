<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\McpTokenController::actionCreate()
 *
 * @var View $this
 * @var McpTokenForm $form
 */

use Hirtz\Skeleton\Models\Forms\McpTokenForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\McpTokenActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('skeleton', 'MCP_TOKEN_CREATE_TITLE'));

echo AccountHeader::make()
    ->model($form->user);

echo AccountSubmenu::make();

echo FormContainer::make()
    ->title(Yii::t('skeleton', 'MCP_TOKEN_CREATE_TITLE'))
    ->form(McpTokenActiveForm::make()
        ->model($form));
