<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\GrantController::actionAuthorize()
 *
 * @var View $this
 * @var string $message
 */

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;

$this->title(Yii::t('skeleton', 'GRANT_AUTHORIZE_ERROR_TITLE'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(
            Alert::make()
                ->danger()
                ->content(P::make()->text($message)),
        ));
