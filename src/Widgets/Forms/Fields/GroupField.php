<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\CustomAttributesAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Legend;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
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

    public function group(GroupCustomAttribute $group): static
    {
        $this->group = $group;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $legend = Legend::make()
            ->addClass('form-label')
            ->text($this->label);

        return \Hirtz\Skeleton\Html\Fieldset::make()
            ->attributes($this->rowAttributes)
            ->addClass('form-group custom-attribute-group')
            ->legend($legend)
            ->content($this->getError(), $this->getHint(), $this->getInput());
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        $owner = $this->model;

        if (!$owner instanceof CustomAttributeInterface) {
            return '';
        }

        $items = $this->getItems($owner);

        $container = Div::make()
            ->addClass('custom-attribute-group-container')
            ->attribute('data-group', $this->property)
            ->content(Div::make()
                ->addClass('custom-attribute-group-items')
                ->attribute('data-group-items', true)
                ->content(...array_map($this->getItem(...), $items)));

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

        return $container->addContent($this->getTemplate($owner), $this->getAddButton(count($items)));
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

        return \Hirtz\Skeleton\Html\Fieldset::make()
            ->addClass('custom-attribute-group-item')
            ->attribute('data-group-item', true)
            ->content($this->getItemHeader(), $fieldset);
    }

    protected function getItemHeader(): ?Stringable
    {
        if (!$this->group->isMultiple()) {
            return null;
        }

        return Div::make()
            ->addClass('custom-attribute-group-item-header')
            ->content(
                $this->group->isSortable() ? DraggableSortGridButton::make() : null,
                Button::make()
                    ->secondary()
                    ->icon('trash')
                    ->type('button')
                    ->attribute('data-group-remove', true)
                    ->attribute('aria-label', Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_BUTTON_REMOVE')),
            );
    }

    protected function getTemplate(Model $owner): string
    {
        $item = $this->group->createItem($owner, self::TEMPLATE_INDEX);

        return Html::tag('template', (string)$this->getItem($item), ['data-group-template' => true]);
    }

    protected function getAddButton(int $count): Stringable
    {
        $max = $this->group->getMaxCount();

        $button = Button::make()
            ->secondary()
            ->text(Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_BUTTON_ADD'))
            ->type('button')
            ->attribute('data-group-add', true);

        return $max !== null && $count >= $max ? $button->attribute('hidden', true) : $button;
    }
}
