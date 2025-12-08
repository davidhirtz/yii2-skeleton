<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\footers;

use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\Ul;
use Hirtz\Skeleton\widgets\forms\FormRow;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

class FormFooter extends Widget
{
    use TagAttributesTrait;
    use ModelWidgetTrait;

    protected array|null $items = null;

    public function items(array|null $items): static
    {
        $this->items = $items;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->attributes['hx-select'] ??= "#wrap";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $this->items ??= array_filter([
            (string)UpdatedAtFooterItem::make()
                ->model($this->model),
            (string)CreatedAtFooterItem::make()
                ->model($this->model),
        ]);

        return $this->items
            ? FormRow::make()
                ->attributes($this->attributes)
                ->addClass('form-footer')
                ->content(Ul::make()
                    ->content(...$this->items))
            : '';
    }
}
