<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\FileUploadButton;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;
use Yii;

/**
 * The container id is derived from the input name rather than generated, since the swap spans two requests and
 * {@see \Hirtz\Skeleton\Helpers\Html::getId()} counts per request.
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

    protected function getFilename(): ?string
    {
        $value = $this->attributes['value'] ?? null;

        return is_string($value) && $value !== ''
            ? $this->getUpload()->getFilename($value)
            : null;
    }

    protected function getFile(): string|Stringable
    {
        return InputGroup::make()
            ->append($this->getRemoveButton())
            ->content($this->getFileLink());
    }

    /** A pending upload has no URL until the record is saved. */
    protected function getFileLink(): string|Stringable
    {
        $filename = (string)$this->getFilename();
        $value = $this->attributes['value'] ?? null;
        $model = $this->model;

        if (!$model instanceof ActiveRecord || $model->getIsNewRecord() || $this->getUpload()->isToken($value)) {
            return Span::make()->addClass('input upload-name')->text($filename);
        }

        return A::make()
            ->addClass('input upload-name')
            ->href($this->getUpload()->getUrl($model, (string)$this->property, $filename))
            ->target('_blank')
            ->text($filename);
    }

    protected function getRemoveButton(): string|Stringable
    {
        return Button::make()
            ->class('btn')
            ->icon('trash')
            ->tooltip(Yii::t('skeleton', 'UPLOAD_REMOVE_TOOLTIP'))
            ->replace($this->getUrl(remove: true), '#' . $this->getContainerId());
    }

    /** The whole group is the one button, so it is one tab stop and a click anywhere on it counts. */
    protected function getUploadButton(): string|Stringable
    {
        return FileUploadButton::make()
            ->accept($this->definition?->getAccept())
            ->button(fn (Button $button): Button => $button
                ->class('input-group upload-select')
                ->type('button')
                ->icon(null)
                ->content(
                    Span::make()
                        ->addClass('input upload-name text-muted')
                        ->text(Yii::t('skeleton', 'UPLOAD_SELECT_BUTTON')),
                    Span::make()
                        ->addClass('input-group-append')
                        ->content(Span::make()
                            ->addClass('btn')
                            ->content(Icon::make()->name('upload'))),
                ))
            ->name('upload')
            ->target('#' . $this->getContainerId())
            ->url($this->getUrl());
    }

    /**
     * A hidden input is not labelable and the picker exists only while no file is chosen, so the label names the row.
     */
    #[Override]
    protected function getLabel(): ?Tag
    {
        return parent::getLabel()?->attribute('for', null);
    }

    /**
     * The type travels with the request, since the model resolves the definitions from it.
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
