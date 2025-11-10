<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\FileUploadAssetBundle;
use Override;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * @property ActiveRecord $model
 */
class FileUploadInputWidget extends InputWidget
{
    public array|string $url = '';

    public ?int $maxChunkSize = null;
    public $attribute = 'upload';
    public ?string $target = null;

    #[Override]
    public function init(): void
    {
        $this->registerClientScript();
        parent::init();
    }

    public function run(): string
    {
        return Html::tag('file-upload', $this->renderInputHtml('file'), [
            'data-url' => Url::to($this->url),
            'data-chunk-size' => $this->maxChunkSize,
            'data-target' => $this->target,
        ]);
    }

    protected function registerClientScript(): void
    {
        $this->getView()->registerAssetBundle(FileUploadAssetBundle::class);
    }
}
