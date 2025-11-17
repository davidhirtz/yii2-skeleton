<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagCardTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class Panel extends Widget
{
    use ContainerWidgetTrait;
    use GridTrait;
    use TagCardTrait;

    public array $panelAttributes = ['class' => 'form-group form-group-horizontal'];
    public array $contentOptions = ['class' => 'col-form-content'];

    protected array $buttons = [];

    public function buttons(string|Stringable|null ...$buttons): static
    {
        $this->buttons = array_values(array_filter($buttons));
        return $this;
    }

    public function addButtons(Stringable|string|null ...$buttons): static
    {
        $this->buttons[] = [...$this->buttons, ...array_values(array_filter($buttons))];
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $this->title ??= Yii::t('skeleton', 'Operations');

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
                ButtonToolbar::make()
                    ->content(...$this->buttons)
            );
        }

        return Div::make()
            ->attributes($this->panelAttributes)
            ->content($content);
    }
}
