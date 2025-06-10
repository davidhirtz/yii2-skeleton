<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets;

use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;

class Flashes extends Widget
{
    public array $alerts;
    public bool $showStatusIcon = true;

    public function init(): void
    {
        $this->alerts ??= Yii::$app->getSession()->getAllFlashes();
        parent::init();
    }

    public function render(): string
    {
        if (!$this->alerts) {
            return '';
        }

        $content = '';

        foreach ($this->alerts as $status => $alerts) {
            $content .= $this->renderAlerts($status, $alerts);
        }

        return Container::make()
            ->attribute('id', 'flashes')
            ->html($content)
            ->render();
    }

    protected function renderAlerts(string $status, array|string $messages): string
    {
        return is_array($messages)
            ? array_reduce($messages, fn ($carry, $item) => $carry . $this->renderAlert($status, $item), '')
            : $this->renderAlert($status, $messages);
    }

    protected function renderAlert(string $status, string $message): string
    {
        if ($status === 'error') {
            $status = 'danger';
        }

        $icon = $this->showStatusIcon ? $this->getStatusIcon($status) : null;

        return Alert::make()
            ->html($message)
            ->icon($icon)
            ->status($status)
            ->render();
    }

    public function getStatusIcon(string $status): ?string
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
