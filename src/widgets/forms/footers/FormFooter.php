<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\footers;

use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormFooter extends Widget
{
    use ModelWidgetTrait;

    protected array|null $items = null;

    public function items(array|null $items): static
    {
        $this->items = $items;
        return $this;
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
                ->addClass('form-footer')
                ->content(Ul::make()
                    ->content(...$this->items))
            : '';
    }
}