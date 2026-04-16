<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Pre;
use Hirtz\Skeleton\Html\Th;
use Hirtz\Skeleton\Models\Log;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @property LogDataProvider $provider
 */
class LogGridView extends GridView
{
    protected string $layout = '{items}';

    protected array $tableAttributes = [
        'class' => 'log-table table table-striped',
    ];

    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [
            $this->getDateColumn(),
            $this->getLevelColumn(),
            $this->getMessageColumn(),
        ];

        parent::configure();
    }

    protected function getDateColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('date')
            ->format('date')
            ->title(Yii::t('skeleton', 'Date'))
            ->nowrap()
            ->width(150);
    }

    protected function getLevelColumn(): Column
    {
        return Column::make()
            ->title(Yii::t('skeleton', 'Level'))
            ->content($this->getLevelColumnContent(...))
            ->width(100);
    }

    protected function getLevelColumnContent(Log $model): string|Stringable
    {
        return Div::make()
            ->class($this->getLevelCssClass($model->level))
            ->content(ucfirst($model->level));
    }

    protected function getMessageColumn(): Column
    {
        return Column::make()
            ->title(Yii::t('skeleton', 'Error'))
            ->content($this->getMessageColumnContent(...));
    }

    protected function getMessageColumnContent(Log $log): array
    {
        $content = [
            Div::make()
                ->text($log->message)
                ->class('strong'),
        ];

        if ($log->category) {
            $content[] = Div::make()
                ->text($log->category)
                ->class('log-category small');
        }

        if ($log->content) {
            $content[] = Div::make()
                ->content(Pre::make()
                    ->class('log-content small')
                    ->text(rtrim($log->content)));
        }

        return $content;
    }

    protected function getLevelCssClass(string $level): string
    {
        return "badge badge-$level";
    }
}
