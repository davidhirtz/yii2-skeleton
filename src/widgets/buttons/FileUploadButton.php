<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\buttons;

use Hirtz\Skeleton\assets\FileUploadAssetBundle;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\custom\FileUpload;
use Hirtz\Skeleton\html\Input;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagIconTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\widgets\Widget;
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
    public array $inputAttributes = [];

    protected ?string $target = null;

    public function accept(?string $accept): static
    {
        $this->inputAttributes['accept'] = $accept;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->inputAttributes['multiple'] = $multiple;
        return $this;
    }

    public function name(string $name): static
    {
        $this->inputAttributes['name'] = $name;
        return $this;
    }

    public function target(?string $target): static
    {
        $this->target = $target;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->inputAttributes['name'] ??= 'upload';

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
                    ->primary()
                    ->text($this->label)
                    ->icon($this->icon),
                Input::make()
                    ->attributes($this->inputAttributes)
                    ->attribute('hidden', true)
                    ->type('file')
            );
    }

    protected function registerClientScript(): void
    {
        $this->view->registerAssetBundle(FileUploadAssetBundle::class);
    }
}
