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
     * @var string
     */
    public $defaultItem;

    /**
     * @var string
     */
    public $defaultValue;

    /**
     * @var string
     */
    public $paramName;

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var bool
     */
    public $encodeLabel = false;

    /**
     * Sets default label.
     */
    public function init()
    {
        if ($this->defaultItem === null) {
            $this->defaultItem = Yii::t('skeleton', 'Show All');
        }

        if ($this->items) {
            $this->dropdown['items'] = $this->items;
        }

        if ($isActive = Yii::$app->getRequest()->get($this->paramName)) {
            if ($this->defaultItem != false && isset($this->dropdown['items'])) {
                array_unshift($this->dropdown['items'],
                    ['label' => $this->defaultItem, 'url' => Url::current([$this->paramName => $this->defaultValue])],
                    '<div class="dropdown-divider"></div>'
                );
            }
        }

        if ($isActive) {
            Html::addCssClass($this->options, 'is-active');
        }

        parent::init();
    }
}