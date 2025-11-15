<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\interfaces;

use davidhirtz\yii2\skeleton\html\Td;
use davidhirtz\yii2\skeleton\html\Th;
use yii\base\Model;

interface ColumnInterface
{
    public function renderHeader(): Th;
    public function renderBody(Model $model, string|int $key, int $index): Td;
}
