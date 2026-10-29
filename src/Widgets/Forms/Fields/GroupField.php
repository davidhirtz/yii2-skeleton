<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\CustomAttributesAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\ReorderButton;
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class GroupField extends Field
{
    final public const string TEMPLATE_INDEX = '__INDEX__';
    final public const string POSITION_PLACEHOLDER = '__POSITION__';

    protected GroupCustomAttribute $group;

    /** Resolved in {@see getInput()}, the first point the owner is known to hold custom attributes. */
    protected bool $single = false;
    protected bool $collapsible = false;

    /**
     * Set once the first row has rendered, since a field carries its input's id only from then; a collapsible row's
     * toggle stands in for its fields.
     */
    protected Fieldset|Field|null $firstControl = null;
    protected ?string $firstToggleId = null;

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    public function single(bool $single = true): static
    {
        $this->single = $single;
        return $this;
    }

    public function group(GroupCustomAttribute $group): static
    {
        $this->group = $group;
        return $this;
    }

    /** A `<fieldset>`, since a row's `<label for>` labels one control and cannot say the controls belong together. */
    #[Override]
    protected function renderContent(): string|Stringable
    {
        return \Hirtz\Skeleton\Html\Fieldset::make()
            ->addClass('custom-attribute-group-fieldset')
            ->attribute('aria-labelledby', $this->label ? $this->getId() : null)
            ->content(parent::renderContent());
    }

    /**
     * A `<label for>` labels one control, so the label points at the first field and the `<fieldset>` carries
     * `aria-labelledby` instead.
     */
    #[Override]
    protected function getLabel(): ?Tag
    {
        if (!$this->label) {
            return null;
        }

        $for = $this->getLabelledFieldId();

        // `addAttributes()`, since `attributes()` would replace the `for` set above it.
        return ($for ? Label::make()->for($for) : Div::make())
            ->addAttributes($this->labelAttributes)
            ->addClass('label')
            ->attribute('id', $this->getId())
            ->text($this->label);
    }

    /**
     * {@see Field::renderContent()} renders the content before the label, so the fields carry their rendered ids by
     * here; an empty or disabled field is skipped.
     */
    protected function getLabelledFieldId(): ?string
    {
        if ($this->firstToggleId !== null) {
            return $this->firstToggleId;
        }

        $rows = $this->firstControl instanceof Fieldset ? $this->firstControl->getRows() : [$this->firstControl];

        foreach ($rows as $row) {
            if ($row instanceof Field && !$row->isDisabled() && $row->render() !== '') {
                return $row->getId();
            }
        }

        return null;
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        $owner = $this->model;

        if (!$owner instanceof CustomAttributeInterface) {
            return '';
        }

        $items = $this->getItems($owner);
        $this->single = $this->isSingleField($items[0] ?? $this->group->createItem($owner, '0'));
        $this->collapsible = $this->group->isMultiple() && !$this->single;

        $container = Div::make()
            ->addClass('custom-attribute-group')
            ->attribute('data-group', $this->property)
            // Without it, removing the last row posts nothing for the group and the stored rows survive the save.
            // It goes first, as a row's input names overwrite it in PHP's parse.
            ->content(
                $this->group->isMultiple()
                    ? Input::make()->attributes([
                    'type' => 'hidden',
                    'name' => (string)($this->attributes['name'] ?? ''),
                    'value' => '',
                ])
                    : null,
                Div::make()
                    ->addClass('custom-attribute-group-items')
                    ->attribute('data-group-items', true)
                    ->attribute(
                        'data-reorder-announcement',
                        $this->group->isReorderable()
                        ? Yii::t('skeleton', 'GRID_REORDER_TEXT', ['position' => '{position}', 'count' => '{count}'])
                        : null
                    )
                    ->content(...$this->getItemElements($items))
            );

        if (!$this->group->isMultiple()) {
            return $container;
        }

        if ($this->collapsible) {
            $container->attribute('data-group-position', $this->getPositionTitle(self::POSITION_PLACEHOLDER));
        }

        $container->attribute('data-group-min', $this->group->getMinCount())
            ->attribute('data-group-max', $this->group->getMaxCount())
            ->attribute('data-group-placeholder', self::TEMPLATE_INDEX)
            ->attribute('data-group-placeholder-id', Html::getInputIdByName(self::TEMPLATE_INDEX));

        if ($this->group->isReorderable()) {
            $container->attribute('data-group-reorderable', true);
        }

        $this->view->registerAssetBundle(CustomAttributesAssetBundle::class);

        return $container->addContent($this->getTemplate($owner), $this->getFooter(count($items)));
    }

    /** Counts the rendered fields, not the declared attributes: a translatable one is a field per language. */
    protected function isSingleField(CustomAttributeGroupItem $item): bool
    {
        $count = 0;

        foreach ($item->getCustomAttributeDefinitions() as $definition) {
            if ($definition->isVisible($item)) {
                $count += count($definition->getAttributeNames($item));
            }
        }

        return $count === 1;
    }

    /**
     * @return list<CustomAttributeGroupItem>
     */
    protected function getItems(Model&CustomAttributeInterface $owner): array
    {
        $items = array_values($owner->getCustomAttributeItems((string)$this->property));

        if ($this->group->isMultiple()) {
            for ($index = count($items); $index < $this->group->getMinCount(); $index++) {
                $items[] = $this->group->createItem($owner, (string)$index);
            }
        }

        return $items;
    }

    /**
     * @param list<CustomAttributeGroupItem> $items
     * @return list<Stringable>
     */
    protected function getItemElements(array $items): array
    {
        $elements = [];
        $reorderable = count($items) > 1;

        // The first row's control is what the label points at, and the template is deliberately not one of
        // these: its ids carry the placeholder and its inputs are not in the document.
        foreach ($items as $index => $item) {
            $control = $this->getItemControl($item, $reorderable);
            $this->firstControl ??= $control;
            $this->firstToggleId ??= $this->collapsible ? $this->getItemId($item) . '-toggle' : null;

            $elements[] = $this->getItem($item, $control, $index + 1, $item->hasErrors(), $reorderable);
        }

        return $elements;
    }

    protected function getItem(
        CustomAttributeGroupItem $item,
        Field|Fieldset $control,
        int|string $position,
        bool $expanded,
        bool $reorderable,
    ): Stringable {
        $element = Div::make()
            ->addClass('custom-attribute-group-item')
            ->attribute('data-group-item', true);

        if (!$this->collapsible) {
            return $element->content($control);
        }

        $id = $this->getItemId($item);

        return $element->content(
            InputGroup::make()
                ->append(...$this->getItemButtons($reorderable))
                ->content($this->getToggleButton($item, $position, $id, $expanded)),
            Div::make()
                ->addClass('custom-attribute-group-item-body')
                ->attribute('data-group-body', true)
                ->attribute('id', "$id-fields")
                ->attribute('hidden', $expanded ? null : true)
                ->content($control),
        );
    }

    /**
     * Derived from the form name rather than generated, since the script rewrites the placeholder in every id of the
     * re-inserted template.
     */
    protected function getItemId(CustomAttributeGroupItem $item): string
    {
        return Html::getInputIdByName($item->formName());
    }

    protected function getItemControl(CustomAttributeGroupItem $item, bool $reorderable): Field|Fieldset
    {
        return ($this->single ? $this->getField($item, $reorderable) : null) ?? $this->getFieldset($item);
    }

    /** The group's label names the one field, so the field drops its own label and row. */
    protected function getField(CustomAttributeGroupItem $item, bool $reorderable): ?Field
    {
        foreach ($item->getCustomAttributeDefinitions() as $definition) {
            if ($definition->isVisible($item)) {
                return $definition->getField($item)
                    ->form($this->form)
                    ->label('')
                    ->showRow(false)
                    ->append(...$this->getItemButtons($reorderable));
            }
        }

        return null;
    }

    protected function getFieldset(CustomAttributeGroupItem $item): Fieldset
    {
        $fieldset = Fieldset::make()
            ->model($item)
            ->form($this->form)
            ->rows(array_keys($item->getCustomAttributeDefinitions()));

        $title = $this->collapsible ? $this->group->getTitleAttribute() : null;

        if ($title === null) {
            return $fieldset;
        }

        // A `prepare()` closure runs after `Fieldset::configure()` resolved the rows into fields and before any
        // of them configures itself, which is what lets it reach the one the preview reads.
        return $fieldset->prepare(static function (Fieldset $fieldset) use ($title): void {
            foreach ($fieldset->getRows() as $row) {
                if ($row instanceof Field && $row->property === $title) {
                    $row->attribute('data-group-title-input', true);
                }
            }
        });
    }

    protected function getToggleButton(
        CustomAttributeGroupItem $item,
        int|string $position,
        string $id,
        bool $expanded,
    ): Stringable {
        $value = $this->getItemTitle($item);
        $fallback = $value === '';

        $title = Span::make()
            ->attribute('data-group-title', true)
            // The script rewrites only the rows that fell back, the rest being what the user typed.
            ->attribute('data-group-title-position', $fallback ?: null)
            ->text($fallback ? $this->getPositionTitle($position) : $value);

        // `.input` rather than `.btn`: the row reads as the control it stands in for, the way a filled upload
        // field does.
        return \Hirtz\Skeleton\Html\Button::make()
            ->addClass('input custom-attribute-group-item-toggle')
            ->attribute('id', "$id-toggle")
            ->attribute('data-group-toggle', true)
            ->attribute('aria-controls', "$id-fields")
            ->attribute('aria-expanded', $expanded ? 'true' : 'false')
            ->content(Icon::make()->name('chevron-down'), $title);
    }

    protected function getPositionTitle(int|string $position): string
    {
        $placeholder = $this->group->getPlaceholder();

        return $placeholder === null
            ? Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ITEM_FORMAT', ['position' => $position])
            : Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ITEM_PLACEHOLDER_FORMAT', [
                'placeholder' => $placeholder,
                'position' => $position,
            ]);
    }

    /**
     * Rendered by the title attribute's own definition, so a select reads as its option label rather than its value.
     */
    protected function getItemTitle(CustomAttributeGroupItem $item): string
    {
        $name = $this->group->getTitleAttribute();

        if ($name === null) {
            return '';
        }

        $value = $item->getCustomAttribute($name)?->formatValue($item, $item->getAttribute($name));

        return is_array($value) ? '' : trim((string)$value);
    }

    /**
     * @return list<Stringable>
     */
    protected function getItemButtons(bool $reorderable): array
    {
        if (!$this->group->isMultiple()) {
            return [];
        }

        return array_values(array_filter([
            // One row has nowhere to move to; the script hides it again as rows come and go.
            $this->group->isReorderable()
                ? ReorderButton::make()
                ->class('btn')
                ->attribute('hidden', $reorderable ? null : true)
                : null,
            $this->getRemoveButton(),
        ]));
    }

    protected function getRemoveButton(): Button
    {
        $label = Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_REMOVE_BUTTON');

        return Button::make()
            ->class('btn')
            ->icon('trash')
            ->type('button')
            ->attribute('data-group-remove', true)
            // `includes/tooltips.ts` moves the `title` into an element of its own and removes the attribute, so
            // the button would otherwise be left without an accessible name.
            ->attribute('aria-label', $label)
            ->tooltip($label);
    }

    protected function getTemplate(Model $owner): string
    {
        $item = $this->group->createItem($owner, self::TEMPLATE_INDEX);
        $control = $this->getItemControl($item, true);

        // The template is the row a click is about to add, so it arrives expanded, its number is the one the
        // script writes once it knows where the row landed, and it is never the only row there is.
        $content = (string)$this->getItem($item, $control, self::POSITION_PLACEHOLDER, true, true);

        return Html::tag('template', $content, ['data-group-template' => true]);
    }

    protected function getFooter(int $count): Stringable
    {
        return Div::make()
            ->addClass('custom-attribute-group-footer')
            ->content($this->getEmptyAddButton($count), $this->getCountHint(), $this->getAddButton($count));
    }

    protected function getEmptyAddButton(int $count): ?Stringable
    {
        if ($this->group->getMinCount() > 0) {
            return null;
        }

        $placeholder = $this->group->getPlaceholder();

        $label = $this->group->getAddButtonLabel() ?? ($placeholder === null
            ? Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ADD_EMPTY_BUTTON')
            : Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ADD_PLACEHOLDER_BUTTON', ['placeholder' => $placeholder]));

        $button = Button::make()
            ->secondary()
            ->icon('plus')
            ->text($label)
            ->type('button')
            ->attribute('data-group-add-empty', true);

        return $count > 0 ? $button->attribute('hidden', true) : $button;
    }

    protected function getCountHint(): ?Stringable
    {
        $min = $this->group->getMinCount();
        $max = $this->group->getMaxCount();

        $text = match (true) {
            $min > 0 && $max !== null => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_MIN_MAX_COUNT_HINT', [
                'min' => $min,
                'max' => $max,
            ]),
            $min > 0 => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_MIN_COUNT_HINT', ['min' => $min]),
            $max !== null => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_MAX_COUNT_HINT', ['max' => $max]),
            default => null,
        };

        return $text ? Div::make()->addClass('form-hint')->text($text) : null;
    }

    protected function getAddButton(int $count): Stringable
    {
        $max = $this->group->getMaxCount();
        $label = Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ADD_BUTTON');

        $button = Button::make()
            ->secondary()
            ->icon('plus')
            ->type('button')
            ->attribute('data-group-add', true)
            ->attribute('aria-label', $label);

        $hidden = ($max !== null && $count >= $max) || ($count === 0 && $this->group->getMinCount() === 0);

        return $hidden ? $button->attribute('hidden', true) : $button;
    }
}
