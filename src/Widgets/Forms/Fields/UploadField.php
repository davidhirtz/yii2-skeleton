<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\FileUploadButton;
use Override;
use Stringable;
use Yii;

/**
 * The file picker of {@see UploadCustomAttribute}: the chunked upload of {@see FileUploadButton} pointed at
 * {@see \Hirtz\Skeleton\Modules\Admin\Controllers\UploadController}, which answers with this field re-rendered.
 *
 * The container id is derived from the input name rather than generated, because the swap happens across two
 * requests — {@see \Hirtz\Skeleton\Helpers\Html::getId()} counts per request and would answer differently in each.
 */
class UploadField extends Field
{
    protected ?UploadCustomAttribute $definition = null;

    public function definition(?UploadCustomAttribute $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['type'] = 'hidden';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        parent::configure();
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        return Div::make()
            ->addClass('upload')
            ->attribute('id', $this->getContainerId())
            ->content(
                $this->getFilename() ? $this->getFile() : $this->getUploadButton(),
                Input::make()->attributes($this->attributes),
            );
    }

    protected function getContainerId(): string
    {
        return $this->getId() . '-upload';
    }

    /**
     * The name the file is offered under: what the record holds, or what the pending upload will be stored as.
     */
    protected function getFilename(): ?string
    {
        $value = $this->attributes['value'] ?? null;

        return is_string($value) && $value !== ''
            ? $this->getUpload()->getFilename($value)
            : null;
    }

    protected function getFile(): string|Stringable
    {
        return Div::make()
            ->addClass('upload-file')
            ->content($this->getFileLink(), $this->getRemoveButton());
    }

    /**
     * A pending upload has no URL yet — it is not attached to anything until the record is saved.
     */
    protected function getFileLink(): string|Stringable
    {
        $filename = (string)$this->getFilename();
        $value = $this->attributes['value'] ?? null;
        $model = $this->model;

        if (!$model instanceof ActiveRecord || $model->getIsNewRecord() || $this->getUpload()->isToken($value)) {
            return Span::make()->addClass('upload-name')->text($filename);
        }

        return A::make()
            ->addClass('upload-name')
            ->href($this->getUpload()->getUrl($model, (string)$this->property, $filename))
            ->target('_blank')
            ->text($filename);
    }

    protected function getRemoveButton(): string|Stringable
    {
        return Button::make()
            ->class('btn-icon icon')
            ->icon('xmark')
            ->tooltip(Yii::t('skeleton', 'UPLOAD_BUTTON_REMOVE'))
            ->replace($this->getUrl(remove: true), '#' . $this->getContainerId());
    }

    protected function getUploadButton(): string|Stringable
    {
        return FileUploadButton::make()
            ->label(Yii::t('skeleton', 'UPLOAD_BUTTON_SELECT'))
            ->icon('upload')
            ->accept($this->definition?->getAccept())
            ->button(fn (Button $button): Button => $button->class('btn btn-secondary'))
            ->name('upload')
            ->target('#' . $this->getContainerId())
            ->url($this->getUrl());
    }

    /**
     * A hidden input is not labelable, and the picker the label could name is only in the DOM while no file is
     * chosen — so the label names the row rather than a control.
     */
    #[Override]
    protected function getLabel(): ?Label
    {
        return parent::getLabel()?->attribute('for', null);
    }

    /**
     * The type travels with the request because the definitions do: it is what the model resolves them from.
     *
     * @return array<int|string, mixed>
     */
    protected function getUrl(bool $remove = false): array
    {
        $model = $this->model;
        $attribute = (string)$this->property;
        $type = $model instanceof TypeAttributeInterface ? $model->getType()?->value : null;

        $value = $this->attributes['value'] ?? null;

        return [
            '/admin/upload/create',
            'model' => $model::class,
            'attribute' => $attribute,
            'type' => $type,
            'signature' => $this->getUpload()->sign($model::class, $attribute, $type),
            ...$remove ? ['remove' => 1] : [],
            ...$remove && $this->getUpload()->isToken($value) ? ['token' => $value] : [],
        ];
    }

    protected function getUpload(): Upload
    {
        return Upload::getComponent();
    }
}
