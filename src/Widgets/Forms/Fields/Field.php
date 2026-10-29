<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Forms\Traits\FormWidgetTrait;
use Hirtz\Skeleton\Widgets\Forms\Traits\InputGroupTrait;
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
    use InputGroupTrait;
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
    protected bool $showRow = true;
    protected string $language;

    protected ?string $error = null;
    protected ?string $hint = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        // Make protected
        $this->language = Yii::$app->getI18n()->getContentLanguage();
        parent::__construct($config);
    }

    /**
     * @param array<string, mixed> $labelAttributes
     */
    public function labelAttributes(array $labelAttributes): static
    {
        $this->labelAttributes = $labelAttributes;
        return $this;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
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

    public function getLanguage(): string
    {
        return $this->language;
    }

    /** Off for a field laid out by something else, such as {@see GroupField}'s single-field row. */
    public function showRow(bool $showRow): static
    {
        $this->showRow = $showRow;
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
                // A string: a boolean renders the bare attribute, whose empty value ARIA reads as "not invalid".
                $this->attributes['aria-invalid'] = 'true';
            }
        }

        $this->rowAttributes['data-id'] ??= $this->getId();

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $this->setDescribedBy();

        if ($this->hasCharacterCounter()) {
            $this->attributes['data-character-counter'] ??= true;
        }

        $content = strtr($this->layout, [
            '{input}' => $this->getControl(),
            '{hint}' => $this->getHint(),
            '{error}' => $this->getError(),
        ]);

        return $this->showRow
            ? FormRow::make()
                ->attributes($this->rowAttributes)
                ->header($this->getLabel())
                ->content($content)
            : $content;
    }

    /** A field building its own input group (the date, the colour picker) does so inside {@see getInput()} instead. */
    protected function getControl(): string|Stringable
    {
        return $this->append || $this->prepend
            ? InputGroup::make()
                ->append(...$this->append)
                ->prepend(...$this->prepend)
                ->content($this->getInput())
            : $this->getInput();
    }

    protected function getLabel(): ?Tag
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

    /** A hint holding `{count}` counts the characters typed, kept current by `includes/characterCounter.ts`. */
    protected function getHint(): string|Stringable
    {
        if (!$this->hint) {
            return '';
        }

        $hint = Div::make()
            ->attribute('id', $this->getHintId())
            ->addClass('form-hint');

        if (!$this->hasCharacterCounter()) {
            return $hint->text($this->hint);
        }

        [$before, $after] = explode('{count}', $this->hint, 2);
        $value = $this->model && $this->property ? $this->model->{$this->property} : null;

        return $hint->content(
            Html::encode($before),
            Span::make()
                ->attribute('data-character-count', true)
                ->text((string)mb_strlen(is_scalar($value) ? (string)$value : '')),
            Html::encode($after),
        );
    }

    protected function hasCharacterCounter(): bool
    {
        return $this->hint !== null && str_contains($this->hint, '{count}');
    }

    /** At render time rather than in `configure()`, so a hint a `prepare()` closure added is linked as well. */
    protected function setDescribedBy(): void
    {
        $describedBy = array_filter([
            $this->error ? $this->getErrorId() : null,
            $this->hint ? $this->getHintId() : null,
        ]);

        if ($describedBy) {
            $this->attributes['aria-describedby'] ??= implode(' ', $describedBy);
        }
    }

    protected function getHintId(): string
    {
        return $this->getId() . '-hint';
    }

    protected function getErrorId(): string
    {
        return $this->getId() . '-error';
    }

    protected function getError(): string|Stringable
    {
        return $this->error
            ? Div::make()
                ->attribute('id', $this->getErrorId())
                ->addClass('form-error')
                ->text($this->error)
            : '';
    }

    /**
     * Read by {@see \Hirtz\Skeleton\Widgets\Forms\Fieldset::configure()} before the field configures itself, so a
     * subclass bound to an attribute declares `$property` rather than assigning it in `configure()`.
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
     * Swaps the whole `#wrap` rather than the form, since a type decides more than its fields, and `hx-swap` overrides
     * the body's `show:top` to keep the scroll position.
     */
    public function reloadsForm(): static
    {
        return $this->prepare(function (self $field): void {
            if (!$field->form) {
                return;
            }

            $field->attributes['hx-post'] ??= $field->form->getAction() ?: '';
            $field->attributes['hx-trigger'] ??= 'change';
            $field->attributes['hx-include'] ??= 'closest form';
            $field->attributes['hx-select'] ??= '#wrap';
            $field->attributes['hx-target'] ??= '#wrap';
            $field->attributes['hx-swap'] ??= 'outerHTML';
            $field->attributes['hx-headers'] ??= ['X-Form-Reload' => '1'];
        });
    }
}
