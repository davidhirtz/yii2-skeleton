<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Pre;
use Hirtz\Skeleton\Models\Log;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Yii;

/**
 * @property LogDataProvider $provider
 */
class LogGridView extends GridView
{
    public string $layout = '{items}';

    public array $tableAttributes = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    #[Override]
    public function configure(): void
    {
        $this->columns ??= [
            $this->getDateColumn(),
            $this->getLevelColumn(),
            $this->getMessageColumn(),
        ];

        $this->view->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::configure();
    }

    protected function getDateColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('date')
            ->header(Yii::t('skeleton', 'Date'))
            ->headerAttributes(['width' => '150'])
            ->format('date')
            ->nowrap();
    }

    protected function getLevelColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Level'))
            ->headerAttributes(['width' => '100'])
            ->content(fn ($model) => Html::tag('div', ucfirst((string)$model['level']), [
                'class' => $this->getLevelCssClass($model['level']),
            ]));
    }

    protected function getMessageColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Error'))
            ->content(function (Log $log): array {
                $content = [
                    Div::make()
                        ->text($log->message)
                        ->class('strong'),
                ];

                if ($log->category) {
                    $content[] = Div::make()
                        ->text($log->category)
                        ->class('small');
                }

                if ($log->content) {
                    $content[] = Div::make()
                        ->content(Pre::make()
                            ->text(rtrim($log->content))
                            ->class('small'));
                }

                return $content;
            });
    }

    protected function getLevelCssClass(string $level): string
    {
        return "badge badge-$level";
    }
}
