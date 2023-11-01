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
 * @property ActiveRecord $model
 */
class FileUpload extends InputWidget
{
    /**
     * @var array|string|null upload route
     */
    public array|string|null $url = null;

    /**
     * @var array the plugin options. For more information, see the jQuery File Upload options documentation.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options
     */
    public array $clientOptions = [];

    /**
     * @var array the event handlers for the jQuery File Upload plugin.
     * Please refer to the jQuery File Upload plugin web page for possible options.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options#callback-options
     */
    public array $clientEvents = [];

    public int $maxChunkSize = 2_000_000;
    public bool $multiple = true;
    public $attribute = 'upload';
    public string $dropZone = '#files';

    public function init(): void
    {
        $defaultClientOptions = [
            'url' => Url::to($this->url),
            'dropZone' => $this->dropZone,
            'maxChunkSize' => $this->maxChunkSize,
        ];

        $this->clientOptions = [...$defaultClientOptions, ...$this->clientOptions];
        $this->options['multiple'] = $this->multiple;

        $defaultClientEvents = [
            'fileuploaddone' => $this->dropZone ? new JsExpression('function(e,x){$(\'' . $this->dropZone . '\').append(x.result)}') : null,
            'fileuploadprogressall' => new JsExpression('Skeleton.uploadProgress'),
        ];

        $this->clientEvents = [...$defaultClientEvents, ...$this->clientEvents];
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();

        return ($this->hasModel() ? Html::activeFileInput($this->model, $this->attribute, $this->options) : Html::fileInput($this->name, $this->value, $this->options)) .
            '<div id="progress"><div class="bar"></div></div>';
    }

    /**
     * Registers required scripts.
     */
    public function registerClientScript(): void
    {
        $view = $this->getView();
        FileUploadAsset::register($view);

        $options = Json::htmlEncode($this->clientOptions);
        $id = $this->options['id'];

        $js[] = "$('#$id').fileupload($options)";

        if (!empty($this->clientEvents)) {
            foreach ($this->clientEvents as $event => $handler) {
                $js[] = ".on('$event', $handler)";
            }
        }

        $view->registerJs(implode('', $js) . ';');
    }
}