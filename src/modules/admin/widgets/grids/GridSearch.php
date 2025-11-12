<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use Yii;
use yii\base\Widget;
use yii\helpers\Url;

class GridSearch
{
    public array $columnOptions = [
        'options' => [
            'class' => 'ms-auto',
        ],
    ];

    public bool $enableAjax = true;
    public array $formOptions = [];
    public array $inputOptions = [];

    public ?string $value = null;
    public string $paramName = 'q';
    public array|string|null $route = null;
    public readonly string $url;

    public function __construct(protected Widget $grid)
    {
        $this->value ??= Yii::$app->getRequest()->get($this->paramName);
        $this->value = $this->value ? trim((string)$this->value) : null;

        $this->url = $this->route ? Url::to($this->route) : Url::current([
            $this->paramName => null,
            'page' => null,
        ]);

        if ($this->value) {
            $this->inputOptions['autofocus'] ??= true;
            $this->inputOptions['onfocus'] ??= 'this.setSelectionRange(this.value.length,this.value.length);';
        }

        if ($this->enableAjax) {
            $this->setAjaxFormOptions();
        }
    }

    protected function setAjaxFormOptions(): void
    {
        $this->formOptions = [
            'hx-get' => $this->url,
            'hx-push-url' => 'true',
            ...$this->formOptions,
        ];
    }

    public function render(): string
    {
        return Html::beginForm($this->url, 'get', $this->formOptions) . $this->renderInput() . Html::endForm();
    }

    protected function renderInput(): string
    {
        $icon = ArrayHelper::remove($this->inputOptions, 'icon', 'search');
        $type = ArrayHelper::remove($this->inputOptions, 'type', 'search');

        $btn = Button::make()
            ->link()
            ->icon($icon)
            ->type('submit')
            ->render();

        $options = [
            'class' => 'form-control',
            'prepend' => $btn,
            'placeholder' => Yii::t('skeleton', 'Search ...'),
            ...$this->inputOptions
        ];

        return Html::input($type, $this->paramName, $this->value, $options);
    }

    public function getColumn(): array
    {
        return [
            ...$this->columnOptions,
            'content' => $this->render(),
        ];
    }

    public function getKeywords(): array
    {
        return $this->value ? array_filter(explode(' ', $this->value)) : [];
    }
}
