<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @property Module $module
 */
class Dashboard extends Widget
{
    use ContainerTrait;

    /**
     * The links of the panel named `shortcuts`.
     *
     * @use ItemTrait<DashboardItem>
     */
    use ItemTrait;

    protected Module $module;

    /**
     * @var array<int|string, DashboardPanel>
     */
    protected array $panels = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        parent::__construct($config);
    }

    /**
     * Named arguments arrive as string keys, which {@see getPanel()} finds the panel by.
     */
    public function addPanel(?DashboardPanel ...$panels): static
    {
        $this->panels = [...$this->panels, ...array_filter($panels)];
        return $this;
    }

    /**
     * @param Closure(array<int|string, DashboardPanel>): array<int|string, DashboardPanel> $panels
     */
    public function panels(Closure $panels): static
    {
        $this->panels = $panels($this->panels);
        return $this;
    }

    public function getPanel(string $name): ?DashboardPanel
    {
        return $this->panels[$name] ?? null;
    }

    /**
     * @return array<int|string, DashboardPanel>
     */
    public function getPanels(): array
    {
        return $this->panels;
    }

    #[Override]
    protected function configure(): void
    {
        $this->module->dashboard($this);
        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $shortcuts = $this->panels['shortcuts'] ?? null;

        if ($shortcuts instanceof ShortcutsPanel) {
            $shortcuts->items(fn (array $items): array => [...$items, ...$this->items]);
        }

        $panels = array_values($this->panels);
        usort($panels, fn (DashboardPanel $a, DashboardPanel $b) => $a->getOrder() <=> $b->getOrder());

        $content = implode('', array_map(strval(...), $panels));

        return $content !== '' ? Div::make()->class('dashboard')->content($content) : '';
    }
}
