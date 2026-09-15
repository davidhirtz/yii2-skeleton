<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Override;
use Yii;
use yii\base\Model;

/**
 * @template TModel of array|Model = Model
 * @extends LinkColumn<TModel>
 */
class BadgeColumn extends LinkColumn
{
    protected bool $showEmpty = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->headerAttributes = ['class' => 'text-center'];
        $this->linkAttributes = ['class' => 'badge'];

        $this->format ??= 'integer';

        parent::__construct($config);
    }

    #[Override]
    protected function formatValue(mixed $value): string
    {
        return $value || $this->showEmpty
            ? Yii::$app->getFormatter()->format($value, $this->format)
            : '';
    }
}
