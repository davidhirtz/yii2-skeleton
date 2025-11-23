<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\buttons;

use davidhirtz\yii2\skeleton\assets\FileUploadAssetBundle;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\custom\FileUpload;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use yii\db\ActiveRecord;

/**
 * @property ActiveRecord $model
 */
class FileUploadButton extends Widget
{
    use TagAttributesTrait;
    use TagLabelTrait;
    use TagIconTrait;
    use TagUrlTrait;

    public ?int $maxChunkSize = null;
    protected ?string $target = null;
    protected string $name = 'upload';

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function target(?string $target): static
    {
        $this->target = $target;
        return $this;
    }

    protected function configure(): void
    {
        $this->registerClientScript();
        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        return FileUpload::make()
            ->url($this->url)
            ->chunkSize($this->maxChunkSize)
            ->target($this->target)
            ->content(
                Button::make()
                    ->attributes($this->attributes)
                    ->text($this->label)
                    ->icon($this->icon),
                Input::make()
                    ->attribute('hidden', true)
                    ->name($this->name)
                    ->type('file'));
    }

    protected function registerClientScript(): void
    {
        $this->view->registerAssetBundle(FileUploadAssetBundle::class);
    }
}
