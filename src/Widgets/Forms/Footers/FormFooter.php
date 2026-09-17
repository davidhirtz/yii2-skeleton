<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Footers;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use yii\base\Model;

class FormFooter extends Widget
{
    use TagAttributesTrait;
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;

    /**
     * @var list<string|Stringable>|null
     */
    protected array|null $items = null;

    /**
     * @param list<string|Stringable>|null $items
     */
    public function items(array|null $items): static
    {
        $this->items = $items;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        // `:inherited`: the footer is the container, its buttons are what issue the request.
        $this->attributes['hx-select:inherited'] ??= "#wrap";
        $this->attributes['hx-target:inherited'] ??= $this->attributes['hx-select:inherited'];

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $this->items ??= array_values(array_filter([
            (string)UpdatedAtFooterItem::make()
                ->model($this->model),
            (string)CreatedAtFooterItem::make()
                ->model($this->model),
        ]));

        return $this->items
            ? FormRow::make()
                ->attributes($this->attributes)
                ->addClass('form-footer')
                ->content(Ul::make()->items(...$this->items))
            : '';
    }
}
