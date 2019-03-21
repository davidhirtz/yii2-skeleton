<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

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
