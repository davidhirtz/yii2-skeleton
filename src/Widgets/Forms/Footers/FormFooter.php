<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Footers;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
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

    #[Override]
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
