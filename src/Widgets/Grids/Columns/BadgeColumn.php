<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Override;
use Yii;

class BadgeColumn extends LinkColumn
{
    protected ?array $headerAttributes = ['class' => 'text-center'];
    protected array|null|Closure $contentAttributes = ['class' => 'text-center'];
    protected bool $showEmpty = false;

    public function __construct(array $config = [])
    {
        $this->format ??= 'integer';
        $this->badge();

        parent::__construct($config);
    }

    protected function badge(): void
    {
        $this->link(fn (A|Div $tag) => $tag->addClass('badge'));
    }

    #[Override]
    protected function formatValue(mixed $value): string
    {
        return $value || $this->showEmpty
            ? Yii::$app->getFormatter()->format($value, $this->format)
            : '';
    }
}
