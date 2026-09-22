<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\CustomAttributesAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Div;
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

    protected GroupCustomAttribute $group;

    /**
     * Resolved in {@see getInput()}, which is the first point the owner is known to hold custom attributes.
     */
    protected bool $single = false;

    public function group(GroupCustomAttribute $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * A group labels several inputs at once, so its own label is not a `<label for>` — the container names it
     * through `aria-labelledby` instead.
     */
    #[Override]
    protected function getLabel(): ?Tag
    {
        return $this->label
            ? Div::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->attribute('id', $this->getId())
                ->text($this->label)
            : null;
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

        $container = Div::make()
            ->addClass('custom-attribute-group')
            ->attribute('role', 'group')
            ->attribute('aria-labelledby', $this->label ? $this->getId() : null)
            ->attribute('data-group', $this->property)
            ->content(Div::make()
                ->addClass('custom-attribute-group-items')
                ->attribute('data-group-items', true)
                ->content(...array_map($this->getItem(...), $items)));

        if ($this->single) {
            $container->addClass('custom-attribute-group-single');
        }

        if (!$this->group->isMultiple()) {
            return $container;
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

    protected function getItem(CustomAttributeGroupItem $item): Stringable
    {
        $fieldset = Fieldset::make()
            ->model($item)
            ->form($this->form)
            ->rows(array_keys($item->getCustomAttributeDefinitions()));

        if ($this->single) {
            // The group's own label names the field, so the field drops its own. A `prepare()` closure runs after
            // `Fieldset::configure()` resolved the rows into fields and before any of them configures itself,
            // which is what keeps the empty label — `Field::configure()` only fills in a `null` one.
            $fieldset->prepare(static function (Fieldset $fieldset): void {
                foreach ($fieldset->getRows() as $row) {
                    if ($row instanceof Field) {
                        $row->label('');
                    }
                }
            });
        }

        $content = $this->single
            ? [$fieldset, $this->getItemButtons()]
            : [$this->getItemButtons(), $fieldset];

        $element = \Hirtz\Skeleton\Html\Fieldset::make()
            ->addClass('custom-attribute-group-item')
            ->attribute('data-group-item', true)
            ->content(...$content);

        return $this->single ? $element->addClass('form-action') : $element;
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

        return Html::tag('template', (string)$this->getItem($item), ['data-group-template' => true]);
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
