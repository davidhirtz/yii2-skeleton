<?php
namespace davidhirtz\yii2\skeleton\widgets\jui;

use davidhirtz\yii2\skeleton\assets\JuiAsset;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Json;

/**
 * Trait Widget
 * @package davidhirtz\yii2\skeleton\widgets\yui
 */
trait JuiWidgetTrait
{
    /**
     * @var array the HTML attributes for the widget container tag
     */
    public $options = [];

    /**
     * @var array the options for the jQuery UI widget
     */
    public $clientOptions = [];

    /**
     * @var array the event handlers for the jQuery UI widget
     */
    public $clientEvents = [];

    /**
     * Registers a specific jQuery UI widget asset bundle, initializes it with client options and registers related events
     * @param string $name
     * @param string $id
     */
    protected function registerWidget($name, $id = null)
    {
        if ($id === null) {
            $id = $this->options['id'];
        }

        $options = $this->clientOptions ? Json::htmlEncode($this->clientOptions) : '';
        $js[] = "$('#$id').$name($options)";

        if (!empty($this->clientEvents)) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = ".on('{$event}', {$handler})";
            }
        }

        /** @var View $view */
        JuiAsset::register($view = $this->getView());
        $view->registerJs(implode('', $js) . ';');
    }

}