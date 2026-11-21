<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Override;
use Yii;

class SearchModelFilterDropdown extends FilterDropdown
{
    protected string $paramName = 'model';

    #[Override]
    protected function configure(): void
    {
        $this->label ??= Yii::t('skeleton', 'SEARCH_MODEL_LABEL');
        parent::configure();
    }

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && count($this->items) > 1;
    }
}
