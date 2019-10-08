<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use yii\db\ActiveRecord;
use yii\web\JsExpression;

/**
 * Class FileUpload.
 * @package davidhirtz\yii2\skeleton\widgets\form
 *
 * @property ActiveRecord $model
 */
class FileUpload extends \dosamigos\fileupload\FileUpload
{
    use WidgetConfigTrait;

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
     * @var bool
     */
    public $useDefaultButton = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->options['multiple'] = $this->multiple;

        $defaultClientOptions = [
            'dropZone' => $this->dropZone,
            'maxChunkSize' => $this->maxChunkSize,
        ];

        $this->clientOptions = array_merge($defaultClientOptions, $this->clientOptions);

        $defaultClientEvents = [
            'fileuploaddone' => $this->dropZone ? new JsExpression('function(e,x){$(\'' . $this->dropZone . '\').append(x.result)}') : null,
            'fileuploadfail' => new JsExpression('function(e,d){bootbox.alert(d.jqXHR.responseText)}'),
        ];

        $this->clientEvents = array_merge($defaultClientEvents, $this->clientEvents);

        parent::init();
    }
}