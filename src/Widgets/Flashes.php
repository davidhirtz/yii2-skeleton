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

    protected int $limit = 5;

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

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
     * @param array<int|string, mixed>|string $messages
     */
    protected function getAlerts(string $status, array|string $messages): string|Stringable
    {
        $alerts = [];

        foreach (array_count_values($this->flattenMessages($messages)) as $message => $count) {
            $alerts[] = $this->renderMessage((string)$message, $count);
        }

        $folded = count($alerts) > $this->limit
            ? array_splice($alerts, max(1, $this->limit) - 1)
            : [];

        if ($folded) {
            $alerts[] = Html::tag('details', Html::tag('summary', Yii::t('skeleton', 'FLASHES_MORE', [
                'count' => count($folded),
            ])) . Html::tag('ul', implode('', array_map(fn (string $message): string => Html::tag('li', $message), $folded))));
        }

        return implode('', array_map(fn (string $message): string => Html::tag('flash-alert', (string)$this->renderAlert($status, $message)), $alerts));
    }

    /**
     * @param array<int|string, mixed>|string $messages
     * @return list<string>
     */
    protected function flattenMessages(array|string $messages): array
    {
        if (!is_array($messages)) {
            return [$messages];
        }

        $flattened = [];

        foreach ($messages as $message) {
            if (is_array($message) || is_string($message)) {
                $flattened = [...$flattened, ...$this->flattenMessages($message)];
            }
        }

        return $flattened;
    }

    protected function renderMessage(string $message, int $count): string
    {
        return $count > 1 ? $message . ' ' . Html::tag('span', (string)$count, ['class' => 'alert-count']) : $message;
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
