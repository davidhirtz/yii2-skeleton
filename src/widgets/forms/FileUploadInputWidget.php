<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\FileUploadAssetBundle;
use davidhirtz\yii2\skeleton\html\Button;
use Override;
use Yii;
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

    public string $content;
    public ?int $maxChunkSize = null;
    public $attribute = 'upload';
    public ?string $target = null;
    public string $template = '{content}{input}';

    #[Override]
    public function init(): void
    {
        $this->content ??= Button::primary()
            ->text(Yii::t('media', 'Upload Files'))
            ->icon('upload')
            ->render();

        Html::addCssClass($this->options, 'd-none');

        $this->registerClientScript();

        parent::init();
    }

    public function run(): string
    {
        $content = $this->content . $this->renderInputHtml('file');

        return Html::tag('file-upload', $content, [
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
