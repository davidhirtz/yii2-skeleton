<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Closure;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Forms\Footers\FormFooter;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\base\Model;

class ActiveForm extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;

    /**
     * @var array<int|string, mixed>|string|null
     */
    public array|string|null $action = null;

    public bool $hasStickyButtons = true;
    protected string $layout = "{errors}{rows}{buttons}{footer}";

    /**
     * Renders every fieldset `disabled` and drops the buttons, so a record the user may see but not change shows
     * the form it would otherwise be a 403 instead of.
     */
    protected bool $readonly = false;

    /**
     * @var Stringable[]|string[]|false|null
     */
    protected array|false|null $buttons = null;
    protected ?string $submitButtonText = null;

    /**
     * @var Stringable[]|string[]|false|null
     */
    protected array|false|null $footer = null;
    /**
     * @var list<string>
     */
    protected array $excludedErrorProperties = [];

    /**
     * What `rows()` was given, normalized. A row of a form is a label and its content — `Fieldset::$rows` holds
     * those — so what a form itself holds is the fieldsets they sit in.
     *
     * @var list<Fieldset>|null
     */
    protected ?array $fieldsets = null;

    /**
     * @param array<int|string, mixed>|string|null $action
     */
    public function action(array|string|null $action): static
    {
        $this->action = $action ? Url::to($action) : $action;
        return $this;
    }

    /**
     * The closure form is what an outside listener uses to insert a field of its own: the collection is not
     * public, so it is handed the current one and returns the one it wants. Whatever shape it returns is
     * normalized, so the closure may hand back the fieldsets it was given.
     *
     * @param array<mixed>|null|Closure(list<Fieldset>): (array<mixed>|null) $rows
     * @return $this
     */
    public function rows(array|null|Closure $rows): static
    {
        $rows = $rows instanceof Closure ? $rows($this->fieldsets ?? []) : $rows;
        $this->fieldsets = null === $rows ? null : $this->normalizeRows($rows);

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->action ??= Yii::$app->controller instanceof Controller ? Url::current() : null;

        $this->attributes['id'] ??= $this->model ? Inflector::camel2id($this->model->formName()) : $this->getId();
        $this->attributes['hx-select'] ??= "#{$this->getId()}";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-boost'] ??= "true";

        $this->fieldsets = $this->normalizeRows($this->fieldsets ?? $this->getDefaultRows());

        parent::configure();
    }

    /**
     * The rows a form shows when the caller named none. A subclass declares its fields here rather than assigning
     * `$fieldsets`, so whatever it returns is normalized before an `EVENT_CONFIGURE` listener ever sees it.
     *
     * @return array<mixed>
     */
    protected function getDefaultRows(): array
    {
        return $this->model?->safeAttributes() ?: [];
    }

    /**
     * Rows are declared as a flat list of fields, as a list of groups or as fieldsets, and become a list of
     * fieldsets here — so a subclass, a listener and the render all see one shape. Mixing a bare field into a list
     * of groups is refused rather than guessed at: the old sniff asked only the first element and applied its
     * answer to the rest, which rendered a fieldset that never got its model.
     *
     * @param array<mixed> $rows
     * @return list<Fieldset>
     */
    protected function normalizeRows(array $rows): array
    {
        $rows = array_values(array_filter(
            $rows,
            static fn (mixed $row): bool => null !== $row && '' !== $row,
        ));

        $groups = array_filter($rows, static fn (mixed $row): bool => is_array($row) || $row instanceof Fieldset);

        if (!$groups) {
            return $rows ? [$this->createFieldset($rows)] : [];
        }

        if (count($groups) !== count($rows)) {
            throw new InvalidConfigException(static::class . '::rows() takes either a flat list of fields or a '
                . 'list of groups, not both. Wrap the bare fields in a group of their own.');
        }

        return array_map($this->createFieldset(...), $rows);
    }

    /**
     * @param array<mixed>|Fieldset $rows
     */
    protected function createFieldset(array|Fieldset $rows): Fieldset
    {
        return $rows instanceof Fieldset ? $rows : Fieldset::make()->rows($rows);
    }

    protected function renderContent(): string|Stringable
    {
        return Form::make()
            ->attributes($this->attributes)
            ->addClass('form')
            ->action($this->action)
            ->content($this->getContent());
    }

    protected function getContent(): string
    {
        return strtr($this->layout, [
            '{errors}' => $this->getErrors(),
            '{rows}' => $this->getRows(),
            '{buttons}' => $this->getButtons(),
            '{footer}' => $this->getFooter(),
        ]);
    }

    protected function getErrors(): string|Stringable
    {
        return ErrorSummary::make()
            ->title(false)
            ->models($this->model)
            ->excluding($this->excludedErrorProperties);
    }

    protected function getRows(): string|Stringable
    {
        $content = implode('', array_map(
            fn (Fieldset $fieldset): string => (string)$fieldset
                ->attribute('disabled', $this->readonly)
                ->form($this),
            $this->fieldsets ?? [],
        ));

        return $content
            ? Div::make()
                ->class('form-rows')
                ->content($content)
            : '';
    }

    protected function getButtons(): ?Stringable
    {
        if ($this->readonly || false === $this->buttons) {
            return null;
        }

        $content = $this->buttons
            ? ButtonGroup::make()->content(...$this->buttons)
            : Div::make()->content($this->getSubmitButton());

        $row = FormRow::make()
            ->content($content);

        $content = Div::make()
            ->class('form-buttons')
            ->content($row);

        if ($this->hasStickyButtons) {
            $content->addAttributes(['data-sticky' => 'bottom'])
                ->addClass('sticky');
        }

        return $content;
    }

    protected function getSubmitButton(): Stringable
    {
        $this->submitButtonText ??= $this->model instanceof ActiveRecordInterface && $this->model->getIsNewRecord()
            ? Yii::t('skeleton', 'COMMON_CREATE')
            : Yii::t('skeleton', 'COMMON_UPDATE');

        return Button::make()
            ->primary()
            ->text($this->submitButtonText)
            ->type('submit');
    }

    protected function getFooter(): ?Stringable
    {
        if (false === $this->footer) {
            return null;
        }

        return FormFooter::make()
            ->model($this->model)
            ->items($this->footer !== null ? array_values($this->footer) : null);
    }
}
