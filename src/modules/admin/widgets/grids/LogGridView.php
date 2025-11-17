<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Pre;
use davidhirtz\yii2\skeleton\models\Log;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Stringable;
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
    public function renderContent(): string|Stringable
    {
        $this->columns ??= [
            $this->getDateColumn(),
            $this->getLevelColumn(),
            $this->getMessageColumn(),
        ];

        $this->view->registerCss('pre{margin-top: 20px; max-height:200px;}');

        return parent::renderContent();
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
