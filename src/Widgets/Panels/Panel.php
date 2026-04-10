<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
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

    public array $panelAttributes = ['class' => 'form-group form-row'];
    public array $contentOptions = ['class' => 'form-content'];

    protected array $buttons = [];

    public function __construct(array $config = [])
    {
        $this->title ??= Yii::t('skeleton', 'Operations');
        parent::__construct($config);
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
            ->attributes($this->contentOptions)
            ->content(...$this->content);

        if ($this->buttons) {
            $content->addContent(
                Div::make()
                    ->class('btn-group')
                    ->content(...$this->buttons)
            );
        }

        return Div::make()
            ->attributes($this->panelAttributes)
            ->content($content);
    }
}
