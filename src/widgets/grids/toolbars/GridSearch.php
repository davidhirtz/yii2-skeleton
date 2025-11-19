<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\toolbars;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Form;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\web\Request;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use Stringable;
use Yii;
use yii\helpers\Url;

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

        $formAttributes['hx-get'] ??= $this->getUrl();

        return Form::make()
            ->method('get')
            ->attributes($formAttributes)
            ->content($this->getInput());
    }

    public function getInput(): Stringable
    {
        $icon = ArrayHelper::remove($this->inputAttributes, 'icon', 'search');
        $type = ArrayHelper::remove($this->inputAttributes, 'type', 'search');

        $btn = Button::make()
            ->link()
            ->icon($icon)
            ->type('submit')
            ->render();

        $inputAttributes = [
            'class' => 'input',
            'prepend' => $btn,
            'placeholder' => Yii::t('skeleton', 'Search ...'),
            ...$this->inputAttributes
        ];

        if ($this->getValue()) {
            $inputAttributes['autofocus'] ??= true;
            $inputAttributes['onfocus'] ??= 'this.setSelectionRange(this.value.length,this.value.length);';
        }

        return Input::make()
            ->type($type)
            ->name($this->paramName)
            ->value($this->getValue())
            ->attributes($inputAttributes);
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
