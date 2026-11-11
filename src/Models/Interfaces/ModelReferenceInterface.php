<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Db\ActiveRecord;

interface ModelReferenceInterface
{
    public function getModel(): ?ActiveRecord;

    /**
     * What names the owner once it is gone.
     */
    public function getMissingModelName(): string;

    /**
     * @return array<string, int>
     */
    public function getAttributeLabelCounts(): array;
}
