<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\html\Container;
use Stringable;
use Yii;

class Flashes extends Widget
{
    public array $alerts;
    public bool $showStatusIcon = true;

    protected function renderContent(): string|Stringable
    {
        $this->alerts ??= Yii::$app->getSession()->getAllFlashes();

        $content = Container::make()
            ->attribute('id', 'flashes')
            ->addClass('empty-hidden');

        foreach ($this->alerts as $status => $alerts) {
            $content->addContent($this->getAlerts($status, $alerts));
        }

        return $content;
    }

    protected function getAlerts(string $status, array|string $messages): string|Stringable
    {
        return is_array($messages)
            ? array_reduce($messages, fn ($carry, $item) => $carry . $this->getAlerts($status, $item), '')
            : $this->renderAlert($status, $messages);
    }

    protected function renderAlert(string $status, string $message): string|Stringable
    {
        $icon = $this->showStatusIcon ? $this->getStatusIcon($status) : null;

        return Alert::make()
            ->content($message)
            ->icon($icon)
            ->status($status);
    }

    protected function getStatusIcon(string $status): ?string
    {
        return match ($status) {
            'success' => 'check-circle',
            'info' => 'info-circle',
            'warning' => 'exclamation-triangle',
            'danger' => 'exclamation-circle',
            'error' => 'exclamation-circle',
            default => null,
        };
    }
}
