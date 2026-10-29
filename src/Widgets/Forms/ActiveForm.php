<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Closure;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Forms\Footers\FormFooter;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;
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
    protected array|string|null $action = null;

    protected bool $hasStickyButtons = true;

    protected bool $inlineTranslationTabs = true;
    protected bool $warnOnLeave = true;

    protected string $layout = "{errors}{rows}{buttons}{footer}";

    /**
     * Renders every fieldset `disabled` without buttons, so a user who may see but not change a record gets the form
     * instead of a 403.
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
     * @var list<Fieldset>|null
     */
    protected ?array $fieldsets = null;

    private string $translationTabs = '';

    public function hasStickyButtons(bool $hasStickyButtons = true): static
    {
        $this->hasStickyButtons = $hasStickyButtons;
        return $this;
    }

    /**
     * A {@see FormContainer} placing the tabs beside the form turns this off and asks {@see getTranslationTabs()} after
     * the render.
     */
    public function inlineTranslationTabs(bool $inlineTranslationTabs = true): static
    {
        $this->inlineTranslationTabs = $inlineTranslationTabs;
        return $this;
    }

    public function warnOnLeave(bool $warnOnLeave = true): static
    {
        $this->warnOnLeave = $warnOnLeave;
        return $this;
    }

    /**
     * @param Stringable[]|string[]|false|null $buttons
     */
    public function buttons(array|false|null $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    /**
     * @param list<string> $excludedErrorProperties
     */
    public function excludedErrorProperties(array $excludedErrorProperties): static
    {
        $this->excludedErrorProperties = $excludedErrorProperties;
        return $this;
    }

    /**
     * @param Stringable[]|string[]|false|null $footer
     */
    public function footer(array|false|null $footer): static
    {
        $this->footer = $footer;
        return $this;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function submitButtonText(?string $submitButtonText): static
    {
        $this->submitButtonText = $submitButtonText;
        return $this;
    }

    /**
     * @param array<int|string, mixed>|string|null $action
     */
    public function action(array|string|null $action): static
    {
        $this->action = $action ? Url::to($action) : $action;
        return $this;
    }

    /**
     * @return array<int|string, mixed>|string|null
     */
    public function getAction(): array|string|null
    {
        return $this->action;
    }

    /**
     * Whatever shape the closure returns is normalized, so it may hand back the fieldsets it was given.
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

        $module = Yii::$app->getModule('admin');

        if ($module instanceof AdminModule && $this->hasTranslationToolbar()) {
            $this->attributes['data-translation-layout'] ??= $module->translationLayout;

            // A failed save swaps the form alone, so tabs standing outside it are named for the swap to renew them;
            // naming any element replaces the inherited list, so the flashes are named again.
            if (!$this->inlineTranslationTabs) {
                $this->attributes['hx-select-oob'] ??= "#flashes:beforeend,#{$this->getTranslationTabsId()}";
            }
        }

        $this->flashStaleSaveError();

        if ($this->warnOnLeave && !$this->readonly) {
            $this->attributes['data-unsaved'] ??= Yii::t('skeleton', 'FORM_UNSAVED_CHANGES_CONFIRM');

            // Rendered with input nobody saved yet: a save that failed, or a reload after a type change.
            if ($this->model?->hasErrors() || Request::current()?->isFormReload()) {
                $this->attributes['data-dirty'] ??= true;
            } elseif ($this->isSavedRecord()) {
                // The submit button reads muted until a change, but stays a button: a save may do more than it shows.
                $this->attributes['data-pristine'] ??= true;
            }
        }

        $this->fieldsets = $this->normalizeRows($this->fieldsets ?? $this->getDefaultRows());

        parent::configure();
    }

    /** A form model wrapping a record answers for that record here. */
    protected function isSavedRecord(): bool
    {
        return $this->model instanceof ActiveRecordInterface && !$this->model->getIsNewRecord();
    }

    /**
     * A subclass declares its fields here rather than assigning `$fieldsets`, so a listener sees them normalized.
     *
     * @return array<mixed>
     */
    protected function getDefaultRows(): array
    {
        return $this->model?->safeAttributes() ?: [];
    }

    /**
     * Mixing a bare field into a list of groups is refused rather than guessed at.
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
            '{rows}' => $this->getStaleSaveInput() . $this->getTranslatedRows(),
            '{buttons}' => $this->getButtons(),
            '{footer}' => $this->getFooter(),
        ]);
    }

    /**
     * A flash rather than a summary line, since the conflict concerns the whole record; the layout renders after the
     * view and picks it up.
     */
    protected function flashStaleSaveError(): void
    {
        if (!$this->model instanceof StaleSaveInterface) {
            return;
        }

        $this->excludedErrorProperties[] = 'loadedAt';
        $error = $this->model->getFirstError('loadedAt');

        if ($error !== null) {
            Application::current()->getSession()->addFlash('warning', Html::encode($error));

            // Saving again is the answer to the warning, so `includes/staleSave.ts` takes the flash away on submit.
            $this->attributes['data-stale-save'] = $error;
        }
    }

    /**
     * A conflict {@see \Hirtz\Skeleton\Models\Traits\StaleSaveTrait} reported takes the current version, so saving once
     * more overwrites knowingly.
     */
    protected function getStaleSaveInput(): string
    {
        if (!$this->model instanceof StaleSaveInterface || !$this->model instanceof BaseActiveRecord || $this->model->getIsNewRecord()) {
            return '';
        }

        $loaded = $this->model->getLoadedVersion();
        $value = $loaded !== null && !$this->model->hasErrors('loadedAt') ? $loaded : $this->model->getStaleSaveVersion();

        return Html::hiddenInput(Html::getInputName($this->model, 'loadedVersion'), (string)$value);
    }

    /** Driven by `includes/translationLayout.ts`; the rows it hides carry `data-language` ({@see Fieldset}). */
    protected function getTranslationToolbar(): string
    {
        if (!$this->hasTranslationToolbar() || !$this->model instanceof I18nAttributeInterface) {
            return '';
        }

        $i18n = Yii::$app->getI18n();
        $missing = $this->model->getMissingTranslationLanguages();

        $items = [
            Li::make()
                ->class('nav-item')
                ->content(Button::make()
                    ->class('nav-link')
                    ->attribute('data-translation-all', true)
                    ->text(Yii::t('skeleton', 'FORM_TRANSLATION_ALL_LABEL'))),
        ];

        foreach ($this->model->getI18nLanguages() as $language) {
            $isMissing = in_array($language, $missing, true);

            $button = Button::make()
                ->class('nav-link')
                ->attribute('data-translation-language', $language)
                ->attribute('data-missing', $isMissing ? true : null)
                ->text(strtoupper($i18n->getLanguageCode($language)));

            $items[] = Li::make()
                ->class('nav-item')
                ->content($isMissing ? $button->icon('exclamation-triangle') : $button);
        }

        return (string)Ul::make()
            ->class('nav tabs translation-tabs')
            ->attribute('id', $this->getTranslationTabsId())
            ->attribute('data-translation-toolbar', $this->getId())
            ->attribute('aria-label', Yii::t('skeleton', 'FORM_TRANSLATION_LABEL'))
            ->content(...$items);
    }

    /** Only where the rows hold a translated field, since a model may declare translations its form leaves out. */
    protected function getTranslatedRows(): string
    {
        $rows = (string)$this->getRows();
        $this->translationTabs = str_contains($rows, ' data-language="') ? $this->getTranslationToolbar() : '';

        return ($this->inlineTranslationTabs ? $this->translationTabs : '') . $rows;
    }

    public function getTranslationTabs(): string
    {
        return $this->translationTabs;
    }

    protected function getTranslationTabsId(): string
    {
        return $this->getId() . '-translations';
    }

    protected function hasTranslationToolbar(): bool
    {
        return Yii::$app->getModule('admin') instanceof AdminModule
            && $this->model instanceof I18nAttributeInterface
            && $this->model->getI18nAttributes()
            && count($this->model->getI18nLanguages()) > 1;
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
            ? Yii::t('skeleton', 'FORM_CREATE_BUTTON')
            : Yii::t('skeleton', 'FORM_UPDATE_BUTTON');

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
