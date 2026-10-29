<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Models\Types\Type;
use Override;

/**
 * Reloads the page on every type change, since what a type decides reaches beyond the form and the fields the
 * skeleton knows about.
 */
class TypeSelectField extends SelectField
{
    protected ?string $property = 'type';

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

    /** A single type is kept even when not required, since an empty choice would retype the record. */
    #[Override]
    protected function isHiddenInput(): bool
    {
        return count($this->items) <= 1;
    }

    /**
     * The stored type stays offered even when {@see Type::available()} refuses it, or the next save would silently
     * retype the record.
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
