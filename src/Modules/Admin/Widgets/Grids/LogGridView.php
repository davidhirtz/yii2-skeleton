<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Pre;
use Hirtz\Skeleton\Models\Log;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\PropertyColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
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

    protected function getDateColumn(): PropertyColumn
    {
        return PropertyColumn::make()
            ->property('date')
            ->title(Yii::t('skeleton', 'Date'))
            ->headerAttributes(['width' => '150'])
            ->format('date')
            ->nowrap();
    }

    protected function getLevelColumn(): Column
    {
        return Column::make()
            ->title(Yii::t('skeleton', 'Level'))
            ->headerAttributes(['width' => '100'])
            ->content(fn ($model) => Div::make()
                ->class($this->getLevelCssClass($model['level']))
                ->content(ucfirst((string)$model['level'])));
    }

    protected function getMessageColumn(): Column
    {
        return Column::make()
            ->title(Yii::t('skeleton', 'Error'))
            ->content(function (Log $log): array {
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
            });
    }

    protected function getLevelCssClass(string $level): string
    {
        return "badge badge-$level";
    }
}
