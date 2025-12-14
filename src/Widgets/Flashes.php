<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Container;
use Stringable;
use Yii;

class Flashes extends Widget
{
    protected array $alerts;

    protected function renderContent(): string|Stringable
    {
        $this->alerts ??= Yii::$app->getSession()->getAllFlashes();

        $content = Container::make()
            ->attribute('id', 'flashes')
            ->attribute('hx-swap-oob', 'beforeend:#flashes')
            ->class('flashes hidden-empty');

        foreach ($this->alerts as $status => $alerts) {
            $content->addContent($this->getAlerts($status, $alerts));
        }

        return $content;
    }

    protected function getAlerts(string $status, array|string $messages): string|Stringable
    {
        return is_array($messages)
            ? array_reduce($messages, fn ($carry, $item) => $carry . $this->getAlerts($status, $item), '')
            : Html::tag('flash-alert', $this->renderAlert($status, $messages));
    }

    protected function renderAlert(string $status, string $message): string|Stringable
    {
        return Alert::make()
            ->content($message)
            ->icon($this->getStatusIcon($status))
            ->status($status)
            ->button(Button::make()
                ->class('btn-icon')
                ->attribute('data-close', '')
                ->icon('xmark'));
    }

    protected function getStatusIcon(string $status): ?string
    {
        return match ($status) {
            'success' => 'check-circle',
            'info' => 'info-circle',
            'warning' => 'exclamation-triangle',
            'danger', 'error' => 'exclamation-circle',
            default => null,
        };
    }
}
