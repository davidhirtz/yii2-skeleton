<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class TypeFilterDropdown extends FilterDropdown
{
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->label ??= Yii::t('skeleton', 'Type');
        $this->paramName ??= 'type';

        if ($this->model instanceof TypeAttributeInterface) {
            $this->items = array_map(fn ($item) => $item['plural'] ?? $item['name'], ($this->model::getTypes()));
        }

        parent::configure();
    }

    public function isVisible(): bool
    {
        return parent::isVisible()
            && (!$this->model instanceof TypeAttributeInterface || count($this->model::getTypes()) > 1);
    }
}
