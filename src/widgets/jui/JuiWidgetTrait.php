<?php
namespace davidhirtz\yii2\skeleton\widgets\jui;

use davidhirtz\yii2\skeleton\assets\JuiAsset;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Json;

trait JuiWidgetTrait
{
    /**
     * @var array the HTML attributes for the widget container tag
     */
    public $options = [];

    /**
     * @var array the options for the jQuery UI widget
     */
    public array $clientOptions = [];

    /**
     * @var array the event handlers for the jQuery UI widget
     */
    public array $clientEvents = [];

    protected function registerWidget(string $name, ?string $id = null): void
    {
        if ($id === null) {
            $id = $this->options['id'];
        }

        $options = $this->clientOptions ? Json::htmlEncode($this->clientOptions) : '';
        $js[] = "$('#$id').$name($options)";

        if (!empty($this->clientEvents)) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = ".on('$event', $handler)";
            }
        }

        /** @var View $view */
        JuiAsset::register($view = $this->getView());
        $view->registerJs(implode('', $js) . ';');
    }
}