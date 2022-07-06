<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\bootstrap4\BootstrapAsset;
use yii\bootstrap4\Widget;

/**
 * The ListGroup widget renders a bootstrap 4 list of links.
 */
class ListGroup extends Widget
{
    /**
     * @var array
     */
    public $items = [];

    /**
     * @var bool
     */
    public $encodeLabels = true;

    /**
     * @var array containing global link HTML options
     */
    public $linkOptions = ['class' => 'list-group-item list-group-item-action'];

    /**
     * @var array containing the list HTML options
     */
    public $options = ['class' => 'list-group list-unstyled'];

    /**
     * @return string
     */
    public function run()
    {
        BootstrapAsset::register($this->getView());
        return $this->renderItems();
    }

    /**
     * @return string
     */
    public function renderItems()
    {
        $items = [];

        foreach ($this->items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }

            $items[] = $this->renderItem($item);
        }

        return Html::ul($items, array_merge($this->options, ['encode' => false]));
    }

    /**
     * @param array $item
     * @return string
     */
    protected function renderItem($item)
    {
        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];

        if (!empty($item['icon'])) {
            $label = Html::iconText($item['icon'], $label);
        }

        return Html::a($label, $item['url'] ?? '#', array_merge($this->linkOptions, $item['linkOptions'] ?? []));
    }
}