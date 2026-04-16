<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Closure;
use Hirtz\Skeleton\Assets\FileUploadAssetBundle;
use Hirtz\Skeleton\Html\Custom\FileUpload;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use yii\db\ActiveRecord;

/**
 * @property ActiveRecord $model
 */
class FileUploadButton extends Widget
{
    use TagAttributesTrait;
    use LabelTrait;
    use IconTrait;
    use UrlTrait;

    protected ?int $maxChunkSize = null;
    protected array $inputAttributes = [];

    protected ?string $target = null;

    private ?array $buttonCallbacks = null;

    public function accept(?string $accept): static
    {
        $this->inputAttributes['accept'] = $accept;
        return $this;
    }

    /**
     * @param Closure(Button): Button $callback
     * @return $this
     */
    public function button(Closure $callback): static
    {
        $this->buttonCallbacks[] = $callback;
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

    #[Override]
    protected function configure(): void
    {
        $this->inputAttributes['name'] ??= 'upload';

        $this->registerClientScript();
        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return FileUpload::make()
            ->url($this->url)
            ->chunkSize($this->maxChunkSize)
            ->target($this->target)
            ->content($this->getButton(), $this->getInput());
    }

    protected function getButton(): ?Stringable
    {
        $button = Button::make()
            ->attributes($this->attributes)
            ->primary()
            ->text($this->label)
            ->icon($this->icon);

        return $this->evaluate($this->buttonCallbacks, $button);
    }

    protected function getInput(): ?Stringable
    {
        return Input::make()
            ->attributes($this->inputAttributes)
            ->attribute('hidden', true)
            ->type('file');
    }

    protected function registerClientScript(): void
    {
        $this->view->registerAssetBundle(FileUploadAssetBundle::class);
    }
}
