<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\CustomAttributesAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DraggableSortButton;
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class GroupField extends Field
{
    /**
     * The index of the template item. {@see Html::getInputIdByName()} mangles it, so the ids carry a different token
     * and the script is told both.
     */
    final public const string TEMPLATE_INDEX = '__INDEX__';

    /**
     * Stands in for a row's number in the title a row with nothing typed into it falls back to. The number the
     * server rendered goes stale on every add, remove and move, so the script rewrites it — and the pattern
     * around it is a translation, which the script cannot build for itself.
     */
    final public const string POSITION_PLACEHOLDER = '__POSITION__';

    protected GroupCustomAttribute $group;

    /**
     * Resolved in {@see getInput()}, which is the first point the owner is known to hold custom attributes.
     */
    protected bool $single = false;

    /**
     * Resolved beside {@see $single}: a row of several fields is previewed by its title and expands, one field
     * is the whole row already, and a group holding exactly one row has nothing to fold away.
     */
    protected bool $collapsible = false;

    /**
     * The first row's fields, for the label to point at. {@see getInput()} assigns it and a template item never
     * does — a template's ids are the placeholder and its inputs are not in the document.
     */
    protected ?Fieldset $firstFieldset = null;

    /**
     * The first row's toggle, which the label points at instead: a collapsible row hides its fields, and a
     * `<label for>` naming a hidden control does nothing at all.
     */
    protected ?string $firstToggleId = null;

    public function group(GroupCustomAttribute $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * A group is a set of rows of its own, so it sits in a `<fieldset>` that spaces it off the plain fields
     * around it — and the element is what says the controls inside belong together, which the row's
     * `<label for>` cannot: that one labels the field it points at.
     */
    #[Override]
    protected function renderContent(): string|Stringable
    {
        return \Hirtz\Skeleton\Html\Fieldset::make()
            ->addClass('custom-attribute-group-fieldset')
            ->attribute('aria-labelledby', $this->label ? $this->getId() : null)
            ->content(parent::renderContent());
    }

    /**
     * The label points at the first field of the first row, so clicking it puts the cursor where typing starts.
     * It cannot name the group that way — a `<label for>` labels one control — so the `<fieldset>` around it
     * carries `aria-labelledby` instead, and a group with no row to point at renders a plain `div`.
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
     * {@see Field::renderContent()} renders its content before it asks for the label, so the row's fields have
     * configured themselves by here and carry the ids their inputs were rendered with. A field that rendered
     * nothing is not in the document and a disabled one cannot take the focus, so neither is pointed at.
     */
    protected function getLabelledFieldId(): ?string
    {
        if ($this->firstToggleId !== null) {
            return $this->firstToggleId;
        }

        foreach ($this->firstFieldset?->getRows() ?? [] as $row) {
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
            ->content(Div::make()
                ->addClass('custom-attribute-group-items')
                ->attribute('data-group-items', true)
                ->content(...$this->getItemElements($items)));

        if ($this->single) {
            $container->addClass('custom-attribute-group-single');
        }

        if (!$this->group->isMultiple()) {
            return $container;
        }

        if ($this->collapsible) {
            $container->attribute('data-group-position', Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ITEM_POSITION', [
                'position' => self::POSITION_PLACEHOLDER,
            ]));
        }

        $container->attribute('data-group-min', $this->group->getMinCount())
            ->attribute('data-group-max', $this->group->getMaxCount())
            ->attribute('data-group-placeholder', self::TEMPLATE_INDEX)
            ->attribute('data-group-placeholder-id', Html::getInputIdByName(self::TEMPLATE_INDEX));

        if ($this->group->isSortable()) {
            $container->attribute('data-group-sortable', true);
        }

        $this->view->registerAssetBundle(CustomAttributesAssetBundle::class);

        return $container->addContent($this->getTemplate($owner), $this->getFooter(count($items)));
    }

    /**
     * What decides the layout is how many fields an item *renders*, not how many attributes the group declares: a
     * translatable one is a field per configured language.
     */
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
     * The rows a minimum count demands are rendered empty, rather than left to the add button.
     *
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

        foreach ($items as $index => $item) {
            $fieldset = $this->getFieldset($item);
            $this->firstFieldset ??= $fieldset;
            $this->firstToggleId ??= $this->collapsible ? $this->getItemId($item) . '-toggle' : null;
            $elements[] = $this->getItem($item, $fieldset, $index + 1, $item->hasErrors());
        }

        return $elements;
    }

    protected function getFieldset(CustomAttributeGroupItem $item): Fieldset
    {
        $fieldset = Fieldset::make()
            ->model($item)
            ->form($this->form)
            ->rows(array_keys($item->getCustomAttributeDefinitions()));

        $single = $this->single;
        $title = $this->collapsible ? $this->group->getTitleAttribute() : null;

        // A `prepare()` closure runs after `Fieldset::configure()` resolved the rows into fields and before any
        // of them configures itself, which is what keeps the empty label — `Field::configure()` only fills in a
        // `null` one. The group's own label names a single field, so that one drops its own.
        $fieldset->prepare(static function (Fieldset $fieldset) use ($single, $title): void {
            foreach ($fieldset->getRows() as $row) {
                if (!$row instanceof Field) {
                    continue;
                }

                if ($single) {
                    $row->label('');
                }

                if ($title !== null && $row->property === $title) {
                    $row->attribute('data-group-title-input', true);
                }
            }
        });

        return $fieldset;
    }

    protected function getItem(
        CustomAttributeGroupItem $item,
        Fieldset $fieldset,
        int|string $position,
        bool $expanded,
    ): Stringable {
        $element = \Hirtz\Skeleton\Html\Fieldset::make()
            ->addClass('custom-attribute-group-item')
            ->attribute('data-group-item', true);

        if ($this->single) {
            return $element->addClass('form-action')->content($fieldset, $this->getItemButtons());
        }

        if (!$this->collapsible) {
            return $element->content($this->getItemButtons(), $fieldset);
        }

        $id = $this->getItemId($item);

        return $element->content(
            Div::make()
                ->addClass('custom-attribute-group-item-header')
                ->content($this->getToggleButton($item, $position, $id, $expanded), $this->getItemButtons()),
            Div::make()
                ->addClass('custom-attribute-group-item-body')
                ->attribute('data-group-body', true)
                ->attribute('id', "$id-fields")
                ->attribute('hidden', $expanded ? null : true)
                ->content($fieldset),
        );
    }

    /**
     * Derived from the item's form name rather than generated, since the script re-inserts the template and
     * rewrites the placeholder in every id it carries.
     */
    protected function getItemId(CustomAttributeGroupItem $item): string
    {
        return Html::getInputIdByName($item->formName());
    }

    protected function getToggleButton(
        CustomAttributeGroupItem $item,
        int|string $position,
        string $id,
        bool $expanded,
    ): Stringable {
        $value = $this->getItemTitle($item);
        $fallback = $value === '';

        $title = Div::make()
            ->addClass('custom-attribute-group-item-title')
            ->attribute('data-group-title', true)
            // The script rewrites only the rows that fell back, the rest being what the user typed.
            ->attribute('data-group-title-position', $fallback ?: null)
            ->text($fallback ? Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ITEM_POSITION', ['position' => $position]) : $value);

        return Button::make()
            ->link()
            ->addClass('custom-attribute-group-item-toggle')
            ->type('button')
            ->icon('chevron-down')
            ->text($title)
            ->attribute('id', "$id-toggle")
            ->attribute('data-group-toggle', true)
            ->attribute('aria-controls', "$id-fields")
            ->attribute('aria-expanded', $expanded ? 'true' : 'false');
    }

    /**
     * The row's preview: the title attribute rendered by its own definition, so a select reads as its option
     * label rather than as its value.
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

    protected function getItemButtons(): ?Stringable
    {
        if (!$this->group->isMultiple()) {
            return null;
        }

        return Div::make()
            ->addClass('custom-attribute-group-item-buttons')
            ->content(
                $this->group->isSortable() ? DraggableSortButton::make() : null,
                $this->getRemoveButton(),
            );
    }

    protected function getRemoveButton(): Button
    {
        $label = Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_BUTTON_REMOVE');

        return Button::make()
            ->secondary()
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

        // The template is the row a click is about to add, so it arrives expanded and its number is the one the
        // script writes once it knows where the row landed.
        $content = (string)$this->getItem($item, $this->getFieldset($item), self::POSITION_PLACEHOLDER, true);

        return Html::tag('template', $content, ['data-group-template' => true]);
    }

    protected function getFooter(int $count): Stringable
    {
        return Div::make()
            ->addClass('custom-attribute-group-footer')
            ->content($this->getCountHint(), $this->getAddButton($count));
    }

    /**
     * One `Yii::t()` call per key, repetition included: `yii message` reads the literal arguments of a call site
     * and evaluates nothing, so a key reached through a ternary is deleted on the next regeneration.
     */
    protected function getCountHint(): ?Stringable
    {
        $min = $this->group->getMinCount();
        $max = $this->group->getMaxCount();

        $text = match (true) {
            $min > 0 && $max !== null => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_HINT_MIN_MAX_COUNT', [
                'min' => $min,
                'max' => $max,
            ]),
            $min > 0 => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_HINT_MIN_COUNT', ['min' => $min]),
            $max !== null => Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_HINT_MAX_COUNT', ['max' => $max]),
            default => null,
        };

        return $text ? Div::make()->addClass('form-hint')->text($text) : null;
    }

    /**
     * Icon-only, so it sits at the size of the row buttons above it rather than towering over them.
     */
    protected function getAddButton(int $count): Stringable
    {
        $max = $this->group->getMaxCount();
        $label = Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_BUTTON_ADD');

        $button = Button::make()
            ->secondary()
            ->icon('plus')
            ->type('button')
            ->attribute('data-group-add', true)
            ->attribute('aria-label', $label)
            ->tooltip($label);

        return $max !== null && $count >= $max ? $button->attribute('hidden', true) : $button;
    }
}
