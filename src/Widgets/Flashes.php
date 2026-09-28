<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Stringable;
use Yii;

class Flashes extends Widget
{
    /**
     * @var array<string, array<int|string, string>|string>
     */
    protected array $alerts;

    protected function renderContent(): string|Stringable
    {
        $this->alerts ??= Application::current()->getSession()->getAllFlashes();

        // The container is page furniture, so it must not carry `hx-swap-oob`: the body's `hx-select-oob` delivers
        // the alerts of every response, while an attribute that survives in the DOM is consumed out of the page
        // htmx restores from its history cache, leaving the next response without a target.
        // `polite` for every flash; a failure interrupts through its own `role="alert"`.
        $content = Div::make()
            ->attribute('id', 'flashes')
            ->attribute('aria-live', 'polite')
            ->class('flashes hidden-empty');

        foreach ($this->alerts as $status => $alerts) {
            $content->addContent($this->getAlerts($status, $alerts));
        }

        return $content . $this->renderNetworkErrorTemplate();
    }

    /**
     * Beside the container rather than in it, which would no longer count as empty. `includes/networkError.ts`
     * clones it into the container when a request never reached the server.
     */
    protected function renderNetworkErrorTemplate(): string
    {
        return Html::tag('template', (string)$this->getAlerts('danger', Yii::t('skeleton', 'COMMON_NETWORK_ERROR')), [
            'id' => 'network-error-flash',
        ]);
    }

    /**
     * @param array<int|string, string>|string $messages
     */
    protected function getAlerts(string $status, array|string $messages): string|Stringable
    {
        return is_array($messages)
            ? array_reduce($messages, fn ($carry, $item) => $carry . $this->getAlerts($status, $item), '')
            : Html::tag('flash-alert', (string)$this->renderAlert($status, $messages));
    }

    protected function renderAlert(string $status, string $message): string|Stringable
    {
        return Alert::make()
            ->attribute('role', in_array($status, ['danger', 'error'], true) ? 'alert' : null)
            ->content($message)
            ->icon($this->getStatusIcon($status))
            ->status($status)
            ->button(Button::make()
                ->class('btn-icon icon')
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
