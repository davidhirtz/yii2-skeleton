<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Container;
use Yii;

class Flashes extends Widget
{
    public array $alerts;
    public bool $showStatusIcon = true;

    protected function renderContent(): string
    {
        $this->alerts ??= Yii::$app->getSession()->getAllFlashes();
        $content = '';

        foreach ($this->alerts as $status => $alerts) {
            $content .= $this->renderAlerts($status, $alerts);
        }

        return Container::make()
            ->attribute('id', 'flashes')
            ->addClass('empty-hidden')
            ->content($content)
            ->render();
    }

    protected function renderAlerts(string $status, array|string $messages): string
    {
        return is_array($messages)
            ? array_reduce($messages, fn ($carry, $item) => $carry . $this->renderAlerts($status, $item), '')
            : $this->renderAlert($status, $messages);
    }

    protected function renderAlert(string $status, string $message): string
    {
        if ($status === 'error') {
            $status = 'danger';
        }

        $icon = $this->showStatusIcon ? $this->getStatusIcon($status) : null;

        return Alert::make()
            ->content($message)
            ->icon($icon)
            ->status($status)
            ->render();
    }

    protected function getStatusIcon(string $status): ?string
    {
        return match ($status) {
            'success' => 'check-circle',
            'info' => 'info-circle',
            'warning' => 'exclamation-triangle',
            'danger' => 'exclamation-circle',
            default => null,
        };
    }
}
