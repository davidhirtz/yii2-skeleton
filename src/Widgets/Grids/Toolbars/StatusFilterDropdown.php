<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class StatusFilterDropdown extends FilterDropdown
{
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->label ??= Lang::t('skeleton', 'COMMON_STATUS');
        $this->paramName ??= 'status';

        if ($this->model instanceof StatusAttributeInterface) {
            $this->items = array_map(fn ($item) => $item['name'], ($this->model::getStatuses()));
        }

        parent::configure();
    }
}
