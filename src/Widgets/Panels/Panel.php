<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Traits\CardTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class Panel extends Widget
{
    use ContainerTrait;
    use GridTrait;
    use CardTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $panelAttributes = ['class' => 'form-group form-row'];
    /**
     * @var array<string, mixed>
     */
    protected array $contentAttributes = ['class' => 'form-content'];

    /**
     * @var list<string|Stringable>
     */
    protected array $buttons = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->title ??= Yii::t('skeleton', 'PANEL_OPERATIONS_TITLE');
        parent::__construct($config);
    }

    /**
     * @param array<string, mixed> $panelAttributes
     */
    public function panelAttributes(array $panelAttributes): static
    {
        $this->panelAttributes = $panelAttributes;
        return $this;
    }

    /**
     * @param array<string, mixed> $contentAttributes
     */
    public function contentAttributes(array $contentAttributes): static
    {
        $this->contentAttributes = $contentAttributes;
        return $this;
    }

    public function buttons(string|Stringable|null ...$buttons): static
    {
        $this->buttons = array_values(array_filter($buttons));
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return $this->content || $this->buttons
            ? Card::make()
                ->title($this->title)
                ->collapsed($this->collapsed)
                ->content($this->getPanel())
            : '';
    }

    protected function getPanel(): Stringable
    {
        $content = Div::make()
            ->attributes($this->contentAttributes)
            ->content(...$this->content);

        if ($this->buttons) {
            $content->addContent(ButtonGroup::make()->content(...$this->buttons));
        }

        return Div::make()
            ->attributes($this->panelAttributes)
            ->content($content);
    }
}
