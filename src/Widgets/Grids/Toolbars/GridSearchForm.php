<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Closure;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class GridSearchForm extends Widget
{
    use TagAttributesTrait;
    use GridTrait;
    use IconTrait;

    protected string $value;
    protected string $paramName;

    protected ?Closure $button;
    protected ?Closure $input;
    protected ?Closure $form;

    public function __construct(array $config = [])
    {
        $this->icon ??= 'search';
        parent::__construct($config);
    }

    public function button(?Closure $button): static
    {
        $this->button = $button;
        return $this;
    }

    public function input(?Closure $input): static
    {
        $this->input = $input;
        return $this;
    }

    public function form(?Closure $form): static
    {
        $this->form = $form;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getToolbarItem();
    }

    protected function getToolbarItem(): GridToolbarItem
    {
        return GridToolbarItem::make()
            ->attributes($this->attributes)
            ->addClass('grid-search')
            ->content($this->getForm());
    }

    protected function getForm(): ?Stringable
    {
        $form = Form::make()
            ->attributes([
                'hx-push-url' => 'true',
                'hx-boost' => 'true',
            ])
            ->action($this->grid->search->getUrl())
            ->method('get')
            ->content($this->getInputGroup());

        return $this->form ? ($this->form)($form) : $form;
    }

    protected function getInputGroup(): Stringable
    {
        $button = Button::make()
            ->class('btn')
            ->icon($this->icon)
            ->type('submit')
            ->render();

        if ($this->button) {
            $button = ($this->button)($button);
        }

        return InputGroup::make()
            ->prepend($button)
            ->content($this->getInput());
    }

    protected function getInput(): ?Stringable
    {
        $input = Input::make()
            ->class('input')
            ->type('search')
            ->placeholder(Yii::t('skeleton', 'Search ...'))
            ->name($this->grid->search->paramName)
            ->value($this->grid->search->getValue());

        if ($this->grid->search->getValue()) {
            $input->attribute('onfocus', 'this.setSelectionRange(this.value.length,this.value.length);');
        }

        return $this->input ? ($this->input)($input) : $input;
    }
}
