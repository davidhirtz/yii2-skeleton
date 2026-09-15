<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;
use yii\base\Model;

class StatusFilterDropdown extends FilterDropdown
{
    /**
     * @use ModelTrait<Model|null>
     */
    use ModelTrait;

    #[Override]
    protected function configure(): void
    {
        $this->label ??= Yii::t('skeleton', 'COMMON_STATUS');
        $this->paramName ??= 'status';

        if ($this->model instanceof StatusAttributeInterface) {
            $this->items = array_map(
                static fn (Status $status): string => $status->getName(),
                $this->model::getStatusDefinitions(),
            );
        }

        parent::configure();
    }
}
