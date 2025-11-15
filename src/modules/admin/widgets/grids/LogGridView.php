<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Yii;

/**
 * @property LogDataProvider|null $provider
 */
class LogGridView extends GridView
{
    public string $layout = '{items}';

    public array $tableAttributes = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    #[Override]
    public function init(): void
    {
        $this->provider ??= Yii::createObject(LogDataProvider::class);

        $this->columns ??= [
            $this->getDateColumn(),
            $this->getLevelColumn(),
            $this->getMessageColumn(),
        ];

        $this->view->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::init();
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
            ->content(function ($model) {
                $html = Html::tag('div', Html::encode(trim((string)$model['message'])), ['class' => 'strong']);

                if (isset($model['category'])) {
                    $html .= Html::tag('div', Html::encode($model['category']), ['class' => 'small']);
                }

                if (isset($model['vars'])) {
                    $html .= Html::tag('pre', Html::encode(rtrim((string)$model['vars'])), ['class' => 'small']);
                }

                return $html;
            });
    }

    protected function getLevelCssClass(string $level): string
    {
        return "badge badge-$level";
    }
}
