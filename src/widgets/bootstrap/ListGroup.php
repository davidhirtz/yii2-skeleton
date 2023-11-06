<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\bootstrap4\BootstrapAsset;
use yii\bootstrap4\Widget;

class ListGroup extends Widget
{
    public array $items = [];
    public bool $encodeLabels = true;

    /**
     * @var array containing global link HTML options
     */
    public array $linkOptions = ['class' => 'list-group-item list-group-item-action'];

    public $options = ['class' => 'list-group list-unstyled'];

    public function run(): string
    {
        BootstrapAsset::register($this->getView());
        return $this->renderItems();
    }

    public function renderItems(): string
    {
        $items = [];

        foreach ($this->items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }

            $items[] = $this->renderItem($item);
        }

        return Html::ul($items, [...$this->options, 'encode' => false]);
    }

    protected function renderItem(array $item): string
    {
        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];

        if (!empty($item['icon'])) {
            $label = Html::iconText($item['icon'], $label);
        }

        return Html::a($label, $item['url'] ?? '#', array_merge($this->linkOptions, $item['linkOptions'] ?? []));
    }
}