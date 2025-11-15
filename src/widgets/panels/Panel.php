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

    public function init(): void
    {
        $this->title ??= Yii::t('skeleton', 'Operations');
        parent::init();
    }

    public function buttons(Stringable|string ...$buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    public function addButton(Stringable|string $button): static
    {
        $this->buttons[] = $button;
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
                ButtonToolbar::make()
                    ->content(...$this->buttons));
        }

        return Div::make()
            ->attributes($this->panelAttributes)
            ->content($content);
    }
}
