<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController::actions()
 *
 * @var View $this
 * @var string $name
 * @var string $message
 */

use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;

$this->title($name);

$request = Application::current()->getRequest();
$referrer = $request->getReferrer();

// Another site's page or the failing one itself is no way back.
$backUrl = $referrer
    && parse_url($referrer, PHP_URL_HOST) === $request->getHostName()
    && $referrer !== $request->getAbsoluteUrl()
        ? $referrer
        : ['/admin/dashboard/index'];

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($name)
        ->content(
            Alert::make()
                ->danger()
                ->content(P::make()->text($message)),
            Button::make()
                ->secondary()
                ->icon('arrow-left')
                ->text(Yii::t('skeleton', 'NAV_BACK_LABEL'))
                ->url($backUrl),
        ));
