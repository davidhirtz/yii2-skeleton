<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Forms\Traits\FormWidgetTrait;
use Hirtz\Skeleton\Widgets\Forms\Traits\RowAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

abstract class Field extends Widget
{
    use FormWidgetTrait;
    use LabelTrait;
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;
    use PropertyTrait;
    use RowAttributesTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $labelAttributes = [];

    protected string $layout = '{input}{error}{hint}';
    public string $language;

    protected ?string $error = null;
    protected ?string $hint = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        // Make protected
        $this->language = Yii::$app->language;
        parent::__construct($config);
    }

    public function error(?string $error): static
    {
        $this->error = $error;
        return $this;
    }

    public function hint(?string $hint): static
    {
        $this->hint = $hint;
        return $this;
    }

    public function language(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model && $this->property) {
            $this->label ??= $this->model->getAttributeLabel($this->property);
            $this->error ??= $this->model->getFirstError($this->property);
            $this->hint ??= $this->model->getAttributeHint($this->property);

            $this->attributes['name'] ??= Html::getInputName($this->model, $this->property);
            $this->attributes['id'] ??= Html::getInputIdByName($this->attributes['name']);

            if ($this->model->isAttributeRequired($this->property)) {
                $this->attributes['required'] ??= true;
            }

            if ($this->model->hasErrors($this->property)) {
                $this->attributes['aria-invalid'] = true;
            }
        }

        $this->rowAttributes['data-id'] ??= $this->getId();

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $content = strtr($this->layout, [
            '{input}' => $this->getInput(),
            '{hint}' => $this->getHint(),
            '{error}' => $this->getError(),
        ]);

        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->header($this->getLabel())
            ->content($content);
    }

    protected function getLabel(): ?Stringable
    {
        return $this->label
            ? Label::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->for($this->getId())
                ->text($this->label)
            : null;
    }

    abstract protected function getInput(): string|Stringable;

    protected function getHint(): string|Stringable
    {
        return $this->hint
            ? Div::make()
                ->addClass('form-hint')
                ->text($this->hint)
            : '';
    }

    protected function getError(): string|Stringable
    {
        return $this->error
            ? Div::make()
                ->addClass('form-error')
                ->text($this->error)
            : '';
    }

    /**
     * Read by {@see \Hirtz\Skeleton\Widgets\Forms\Fieldset::configure()} before the field configures itself, so a
     * subclass binding itself to an attribute declares `$property` rather than assigning it in `configure()`.
     */
    public function isSafe(): bool
    {
        return !$this->property || ($this->model?->isAttributeSafe($this->property) ?? false);
    }

    public function isRequired(): bool
    {
        return (bool)($this->attributes['required'] ?? false);
    }

    public function isDisabled(): bool
    {
        return (bool)($this->attributes['disabled'] ?? false);
    }

    /**
     * Posts the form to its own action on change and re-renders the page from the loaded but unsaved record, so a
     * field the rest of the page depends on takes effect without saving.
     * {@see \Hirtz\Skeleton\Web\Request::isFormReload()} tells the action apart.
     *
     * The swap is the whole `#wrap` the body declares, not the form: a type decides more than its fields — the
     * submenu tabs, the header, the record's own noun — and picking those out one selector at a time is what the
     * server already answers by rendering the page. The form's own narrower `hx-select` has to be overridden for
     * that, and `hx-swap` beats the body's `show:top` so the scroll position survives.
     */
    public function reloadsForm(): static
    {
        return $this->prepare(function (self $field): void {
            if (!$field->form) {
                return;
            }

            $field->attributes['hx-post'] ??= $field->form->action ?: '';
            $field->attributes['hx-trigger'] ??= 'change';
            $field->attributes['hx-include'] ??= 'closest form';
            $field->attributes['hx-select'] ??= '#wrap';
            $field->attributes['hx-target'] ??= '#wrap';
            $field->attributes['hx-swap'] ??= 'outerHTML';
            $field->attributes['hx-headers'] ??= ['X-Form-Reload' => '1'];
        });
    }
}
