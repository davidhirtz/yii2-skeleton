<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Closure;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use yii\base\Model;

class GridSearchForm extends Widget
{
    use TagAttributesTrait;
    use GridTrait;
    use IconTrait;

    protected string $value;
    protected string $paramName;

    /**
     * @var list<Closure>|null
     */
    private ?array $buttonClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $formClosures = null;
    /**
     * @var list<Closure>|null
     */
    private ?array $inputClosures = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->icon ??= 'search';
        parent::__construct($config);
    }

    /**
     * @param Closure(Button): Button $button
     */
    public function button(Closure $button): static
    {
        $this->buttonClosures[] = $button;
        return $this;
    }

    /**
     * @param Closure(TextInput): TextInput $input
     */
    public function input(Closure $input): static
    {
        $this->inputClosures[] = $input;
        return $this;
    }

    /**
     * @param Closure(Form): Form $form
     */
    public function form(Closure $form): static
    {
        $this->formClosures[] = $form;
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
        $parts = explode('?', (string)$this->grid->search->getUrl(), 2);

        $form = Form::make()
            ->attributes([
                'hx-push-url' => 'true',
                'hx-boost' => 'true',
            ])
            ->action($parts[0] ?: null)
            ->method('get')
            ->content(...[...$this->getHiddenInputs($parts[1] ?? ''), $this->getInputGroup()]);

        return $this->evaluate($this->formClosures, $form);
    }

    /**
     * A GET form submits its own fields and nothing else — the browser replaces the action's query string with
     * them, and htmx strips it from a boosted form for the same reason — so every parameter the grid's URL
     * carries is a hidden input here, or searching leaves the route it was issued from.
     *
     * @return list<Stringable>
     */
    protected function getHiddenInputs(string $query): array
    {
        $inputs = [];

        foreach (array_filter(explode('&', $query)) as $parameter) {
            $parts = explode('=', $parameter, 2);

            $inputs[] = Input::make()
                ->type('hidden')
                ->name(urldecode($parts[0]))
                ->value(urldecode($parts[1] ?? ''));
        }

        return $inputs;
    }

    protected function getInputGroup(): Stringable
    {
        $button = Button::make()
            ->class('btn')
            ->icon($this->icon)
            ->type('submit');

        return InputGroup::make()
            ->prepend($this->evaluate($this->buttonClosures, $button))
            ->content($this->getInput());
    }

    protected function getInput(): ?Stringable
    {
        $input = TextInput::make()
            ->class('input')
            ->type('search')
            ->placeholder(Yii::t('skeleton', 'GRID_SEARCH_SEARCH'))
            ->name($this->grid->search->paramName)
            ->value($this->grid->search->getValue());

        if ($this->grid->search->getValue()) {
            $input->attribute('onfocus', 'this.setSelectionRange(this.value.length,this.value.length);');
        }

        return $this->evaluate($this->inputClosures, $input);
    }
}
