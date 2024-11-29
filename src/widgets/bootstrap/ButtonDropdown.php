<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\helpers\Url;

class ButtonDropdown extends \yii\bootstrap4\ButtonDropdown
{
    /**
     * @var string|false|null the default item label, set to `false` to disable the default item
     */
    public string|false|null $defaultItem = null;

    /**
     * @var string|null the default item parameter value
     */
    public ?string $defaultValue = null;

    /**
     * @var string|null the parameter name
     */
    public ?string $paramName = null;

    /**
     * @var array containing items as array with "label" and optional "url" keys
     */
    public array $items = [];
    /**
     * @var bool whether the filter text field should be added to the dropdown
     */
    public bool $showFilter = false;

    /**
     * @var string|null the filter text field placeholder text
     */
    public ?string $filterPlaceholder = null;

    /**
     * @var bool whether dropdown is active, if `null` the request will be checked for `paramName`
     */
    public ?bool $isActive = null;

    public $encodeLabel = false;

    public function init(): void
    {
        $this->defaultItem ??= Yii::t('skeleton', 'Show All');
        $this->isActive ??= $this->paramName && Yii::$app->getRequest()->get($this->paramName) !== null;

        if ($this->items) {
            $this->dropdown['items'] = $this->items;
        }

        if ($this->showFilter) {
            if ($this->filterPlaceholder === null) {
                $this->filterPlaceholder = Yii::t('skeleton', 'Filter');
            }

            $label = Html::tag('input', '', [
                'class' => 'dropdown-filter form-control',
                'placeholder' => $this->filterPlaceholder,
            ]);

            array_unshift(
                $this->dropdown['items'],
                ['label' => $label, 'encode' => false],
                '-'
            );
        }

        if ($this->isActive) {
            if ($this->defaultItem !== false && isset($this->dropdown['items'])) {
                array_unshift(
                    $this->dropdown['items'],
                    ['label' => $this->defaultItem, 'url' => Url::current([$this->paramName => $this->defaultValue])],
                    '-'
                );
            }

            Html::addCssClass($this->options, 'is-active');
        }

        parent::init();

        if ($this->showFilter) {
            $this->getView()->registerJs("$('#{$this->options['id']}').dropdownFilter();");
        }
    }

    /**
     * Resets the options id back to widget id which is set to the button id in
     * {@see \yii\bootstrap4\ButtonDropdown::run()}. Otherwise, Bootstrap events don't register on the correct element.
     */
    protected function registerClientEvents(): void
    {
        $this->options['id'] = $this->getId();
        parent::registerClientEvents();
    }
}
