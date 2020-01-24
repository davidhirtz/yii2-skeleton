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
     * @var string the default item label
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
     * @var bool
     */
    public $showFilter = false;

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
            array_unshift($this->dropdown['items'],
                ['label' => '<input type="text" class="dropdown-filter form-control">', 'encode' => false],
                '-'
            );
        }

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

        parent::init();

        if ($this->showFilter) {
            $this->getView()->registerJs("jQuery('#{$this->options['id']}').dropdownFilter();");
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