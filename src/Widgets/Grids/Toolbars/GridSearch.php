<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Stringable;
use Yii;
use Hirtz\Skeleton\Helpers\Url;;

class GridSearch
{
    use ContainerConfigurationTrait;
    use GridTrait;
    use TagUrlTrait;

    protected array $inputAttributes = [];
    protected array $formAttributes = [];
    protected string $paramName = 'q';
    protected array $toolbarItemAttributes = ['class' => 'grid-search'];
    protected ?string $value = null;

    public function __construct(protected Request $request)
    {
    }

    public function inputAttributes(array $attributes): static
    {
        $this->inputAttributes = $attributes;
        return $this;
    }

    public function formAttributes(array $attributes): static
    {
        $this->formAttributes = $attributes;
        return $this;
    }

    public function paramName(string $paramName): static
    {
        $this->paramName = $paramName;
        return $this;
    }

    public function toolbarItemAttributes(array $attributes): static
    {
        $this->toolbarItemAttributes = $attributes;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url ??= Url::current([
            $this->paramName => null,
            'page' => null,
        ]);
    }

    public function value(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): ?string
    {
        if ($this->value === null) {
            $this->value = $this->request->get($this->paramName);
            $this->value = $this->value ? trim((string)$this->value) : null;
        }

        return $this->value;
    }

    public function render(): Stringable
    {
        $formAttributes = $this->formAttributes;
        $formAttributes['hx-push-url'] ??= 'true';
        $formAttributes['hx-boost'] ??= 'true';

        return Form::make()
            ->attributes($formAttributes)
            ->action($this->getUrl())
            ->method('get')
            ->content($this->getInput());
    }

    public function getInput(): Stringable
    {
        $icon = ArrayHelper::remove($this->inputAttributes, 'icon', 'search');
        $type = ArrayHelper::remove($this->inputAttributes, 'type', 'search');

        $btn = Button::make()
            ->addClass('btn')
            ->icon($icon)
            ->type('submit')
            ->render();

        $inputAttributes = [
            'class' => 'input',
            'placeholder' => Yii::t('skeleton', 'Search ...'),
            ...$this->inputAttributes
        ];

        $inputAttributes['autofocus'] = true;

        if ($this->getValue()) {
            $inputAttributes['onfocus'] ??= 'this.setSelectionRange(this.value.length,this.value.length);';
        }

        return InputGroup::make()
            ->prepend($btn)
            ->content(Input::make()
                ->attributes($inputAttributes)
                ->type($type)
                ->name($this->paramName)
                ->value($this->getValue()));
    }

    public function getToolbarItem(): GridToolbarItem
    {
        return GridToolbarItem::make()
            ->attributes($this->toolbarItemAttributes)
            ->content($this->render());
    }

    public function getKeywords(): array
    {
        return array_filter(explode(' ', $this->getValue() ?? ''));
    }
}
