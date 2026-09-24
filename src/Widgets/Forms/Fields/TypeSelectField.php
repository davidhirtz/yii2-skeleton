<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Models\Types\Type;
use Override;

/**
 * Reloads the page whenever the type changes, because what a type decides is open ended: its custom attributes and
 * hidden fields, but also a field another bundle contributes and anything outside the form keyed on the type. A
 * fingerprint over the part the skeleton knows about only ever answered for that part.
 */
class TypeSelectField extends SelectField
{
    public ?string $property = 'type';

    #[Override]
    protected function configure(): void
    {
        // The items decide whether there is anything to reload for, and `prepare()` is only evaluated at the end of
        // the `configure()` chain — so both have to happen before the parent resolves them itself.
        if ($this->model && !$this->items) {
            $this->addItemsFromModel($this->getItemsFromModel());
        }

        if (count($this->items) > 1) {
            $this->reloadsForm();
        }

        parent::configure();

        // A select would post its only option; a hidden input posts nothing unless it is given the value.
        if ($this->isHiddenInput() && in_array($this->attributes['value'] ?? null, [null, ''], true)) {
            $this->attributes['value'] = array_key_first($this->items);
        }
    }

    /**
     * A single type is kept whether or not the attribute is required: an empty choice would retype the record.
     */
    #[Override]
    protected function isHiddenInput(): bool
    {
        return count($this->items) <= 1;
    }

    /**
     * A type the record cannot take is not offered, which is what {@see Type::available()} is for — except the
     * one the record is stored with, or the form would silently retype it on the next save.
     *
     * @return array<int|string, mixed>
     */
    #[Override]
    protected function getItemsFromModel(): array
    {
        return array_filter(
            parent::getItemsFromModel(),
            fn (mixed $item): bool => !$item instanceof Type
                || $item->isAvailableOrStored($this->model, (string)$this->property),
        );
    }
}
