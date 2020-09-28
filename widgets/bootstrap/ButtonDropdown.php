<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\helpers\Url;

/**
 * Class ButtonDropdown.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class ButtonDropdown extends \yii\bootstrap4\ButtonDropdown
{
    /**
     * @var string|false|null the default item label, set to `false` to disable the default item
     */
    public $defaultItem;

    /**
     * @var string the default item parameter value
     */
    public $defaultValue;

    /**
     * @var string the parameter name of the default item
     */
    public $paramName;

    /**
     * @var array containing items as array with "label" and optional "url" keys
     */
    public $items = [];

    /**
     * @var bool whether the label should be HTML-encoded.
     */
    public $encodeLabel = false;

    /**
     * @var bool whether the filter text field should be added to the dropdown
     */
    public $showFilter = false;

    /**
     * @var string the filter text field placeholder text
     */
    public $filterPlaceholder;

    /**
     * Sets default label and adds filter text field.
     */
    public function init()
    {
        if ($this->defaultItem === null) {
            $this->defaultItem = Yii::t('skeleton', 'Show All');
        }

        if ($this->items) {
            $this->dropdown['items'] = $this->items;
        }

        if ($this->showFilter) {
            if ($this->filterPlaceholder === null) {
                $this->filterPlaceholder = Yii::t('skeleton', 'Filter');
            }

            array_unshift($this->dropdown['items'],
                ['label' => Html::tag('input', null, ['class' => 'dropdown-filter form-control', 'placeholder' => $this->filterPlaceholder]), 'encode' => false],
                '-'
            );
        }

        if ($this->paramName) {
            if ($isActive = Yii::$app->getRequest()->get($this->paramName)) {
                if ($this->defaultItem !== false && isset($this->dropdown['items'])) {
                    array_unshift($this->dropdown['items'],
                        ['label' => $this->defaultItem, 'url' => Url::current([$this->paramName => $this->defaultValue])],
                        '-'
                    );
                }
            }

            if ($isActive) {
                Html::addCssClass($this->options, 'is-active');
            }
        }

        parent::init();

        if ($this->showFilter) {
            $this->getView()->registerJs("$('#{$this->options['id']}').dropdownFilter();");
        }
    }

    /**
     * Resets the options id back to widget id which is set to the button id in {@link \yii\bootstrap4\ButtonDropdown::run()}.
     * Otherwise Bootstrap events don't register on the correct element.
     */
    protected function registerClientEvents()
    {
        $this->options['id'] = $this->getId();
        parent::registerClientEvents();
    }
}