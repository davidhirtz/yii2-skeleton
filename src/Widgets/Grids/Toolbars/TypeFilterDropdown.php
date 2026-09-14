<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class TypeFilterDropdown extends FilterDropdown
{
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->label ??= Yii::t('skeleton', 'COMMON_TYPE');
        $this->paramName ??= 'type';

        if ($this->model instanceof TypeAttributeInterface) {
            $items = $this->items ?: $this->model::getTypeDefinitions();
            $this->items = [];

            foreach ($items as $value => $item) {
                if ($item instanceof Type) {
                    if (!$item->isAvailable($this->model)) {
                        continue;
                    }

                    $item = $item->getPlural();
                }

                $this->items[$value] = $item;
            }
        }

        parent::configure();
    }

    #[\Override]
    public function isVisible(): bool
    {
        return parent::isVisible()
            && (!$this->model instanceof TypeAttributeInterface || count($this->model::getTypeDefinitions()) > 1);
    }
}
