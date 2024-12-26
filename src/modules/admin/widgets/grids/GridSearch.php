<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\base\BaseObject;
use yii\base\Widget;
use yii\helpers\Url;

class GridSearch extends BaseObject
{
    public array $columnOptions = [
        'options' => [
            'class' => 'ms-auto',
        ],
    ];

    public bool $enableAjax = true;

    public array $formOptions = [];
    public array $inputOptions = [];

    public string $paramName = 'q';
    public array|string|null $route = null;
    public string $url;
    public ?string $value = null;

    public function __construct(public readonly Widget $owner, $config = [])
    {
        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        $this->value ??= Yii::$app->request->get($this->paramName);
        $this->value = $this->value ? trim($this->value) : null;

        $this->url = $this->route ? Url::to($this->route) : Url::current([
            $this->paramName => null,
            'page' => null,
        ]);

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
        $icon = (string)Icon::tag($icon, ['class' => 'fa-fw']);

        $type = ArrayHelper::remove($this->inputOptions, 'type', 'search');

        $options = [
            'class' => 'form-control',
            'prepend' => Html::submitButton($icon, ['class' => 'btn-transparent']),
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
