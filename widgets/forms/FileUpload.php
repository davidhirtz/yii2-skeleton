<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\FileUploadAsset;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * Class FileUpload.
 * @package davidhirtz\yii2\skeleton\widgets\form
 *
 * @property ActiveRecord $model
 */
class FileUpload extends InputWidget
{
    /**
     * @var string|array upload route
     */
    public $url;

    /**
     * @var array the plugin options. For more information see the jQuery File Upload options documentation.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options
     */
    public $clientOptions = [];

    /**
     * @var array the event handlers for the jQuery File Upload plugin.
     * Please refer to the jQuery File Upload plugin web page for possible options.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options#callback-options
     */
    public $clientEvents = [];

    /**
     * @var int
     */
    public $maxChunkSize = 2000000;

    /**
     * @var bool
     */
    public $multiple = true;

    /**
     * @var string
     */
    public $attribute = 'upload';

    /**
     * @var string
     */
    public $dropZone = '#files';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $defaultClientOptions = [
            'url' => Url::to($this->url),
            'dropZone' => $this->dropZone,
            'maxChunkSize' => $this->maxChunkSize,
        ];

        $this->clientOptions = array_merge($defaultClientOptions, $this->clientOptions);
        $this->options['multiple'] = $this->multiple;

        $defaultClientEvents = [
            'fileuploaddone' => $this->dropZone ? new JsExpression('function(e,x){$(\'' . $this->dropZone . '\').append(x.result)}') : null,
            'fileuploadfail' => new JsExpression('function(e,d){bootbox.alert(d.jqXHR.responseText)}'),
        ];

        $this->clientEvents = array_merge($defaultClientEvents, $this->clientEvents);

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();
        echo $this->hasModel() ? Html::activeFileInput($this->model, $this->attribute, $this->options) : Html::fileInput($this->name, $this->value, $this->options);
    }

    /**
     * Registers required scripts.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        FileUploadAsset::register($view);

        $options = Json::htmlEncode($this->clientOptions);
        $id = $this->options['id'];

        $js[] = "$('#{$id}').fileupload({$options})";

        if (!empty($this->clientEvents)) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = ".on('{$event}', {$handler})";
            }
        }

        $view->registerJs(implode('', $js) . ';');
    }
}