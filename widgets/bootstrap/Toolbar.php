<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use yii\bootstrap4\Html;

/**
 * Class Toolbar
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Toolbar extends \yii\base\Widget
{
    /**
     * @var array|string containing the buttons on the left side of the toolbar
     */
    public $actions = [];

    /**
     * @var array|string containing the links on the right side of the toolbar
     */
    public $links = [];

    /**
     * @var array containing the navbar options.
     */
    public $wrapperOptions = [
        'class' => 'toolbar',
    ];

    /**
     * @var array containing the navbar options.
     */
    public $containerOptions = [
        'class' => 'container',
    ];

    /**
     * @var array containing the navbar options.
     */
    public $toolbarOptions = [
        'class' => 'btn-toolbar justify-content-between',
    ];

    /**
     * @param array|string $items
     * @return string
     */
    protected function renderActions($items)
    {
        return $this->renderItems($items);
    }

    /**
     * @param array|string $items
     * @return string
     */
    protected function renderLinks($items)
    {
        return $this->renderItems($items);
    }

    /**
     * @param array|string $items
     * @param array $options
     * @return string
     */
    protected function renderItems($items, $options = ['class' => 'col'])
    {
        if ($items = array_filter((array)$items)) {
            return Html::tag('div', implode('', $items), $options);
        }

        return '';
    }

    /**
     * @return string
     */
    public function run()
    {
        $actions = $this->renderActions($this->actions);
        $links = $this->renderLinks($this->links);

        if ($actions || $links) {
            $content = Html::tag('div', $actions . $links, $this->toolbarOptions);
            return Html::tag('div', Html::tag('div', $content, $this->containerOptions), $this->wrapperOptions);
        }

        return '';
    }
}