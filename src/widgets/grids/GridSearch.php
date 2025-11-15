<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Form;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\web\Request;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\GridToolbarItem;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use Stringable;
use Yii;
use yii\helpers\Url;

class GridSearch
{
    use ContainerConfigurationTrait;
    use GridTrait;

    protected array $inputAttributes = [];
    protected array $formAttributes = [];
    protected string $paramName = 'q';
    protected array $toolbarItemAttributes = ['class' => 'ms-auto'];
    protected array|string|null $url = null;
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

    public function url(array|string|null $url): static
    {
        $this->url = $url;
        return $this;
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

        $formAttributes['hx-get'] ??= $this->url ? Url::to($this->url) : Url::current([
            $this->paramName => null,
            'page' => null,
        ]);

        return Form::make()
            ->method('get')
            ->attributes($formAttributes)
            ->html($this->getInput());
    }

    protected function getInput(): Stringable
    {
        $icon = ArrayHelper::remove($this->inputAttributes, 'icon', 'search');
        $type = ArrayHelper::remove($this->inputAttributes, 'type', 'search');

        $btn = Button::make()
            ->link()
            ->icon($icon)
            ->type('submit')
            ->render();

        $inputAttributes = [
            'class' => 'form-control',
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
            ->html($this->render());
    }

    public function getKeywords(): array
    {
        return array_filter(explode(' ', $this->getValue() ?? ''));
    }
}
