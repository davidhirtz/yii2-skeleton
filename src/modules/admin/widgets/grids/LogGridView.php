<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Yii;

/**
 * @property LogDataProvider $dataProvider
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
        $this->columns ??= [
            $this->dateColumn(),
            $this->levelColumn(),
            $this->messageColumn(),
        ];

        $this->getView()->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::init();
    }

    protected function dateColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Date'),
            'headerOptions' => ['width' => '150'],
            'contentOptions' => ['class' => 'text-nowrap'],
            'content' => fn ($model) => Yii::$app->getFormatter()->asDatetime(new DateTime($model['date']), 'short')
        ];
    }

    protected function levelColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Level'),
            'headerOptions' => ['width' => '100'],
            'content' => fn ($model) => Html::tag('div', ucfirst((string) $model['level']), [
                'class' => $this->getLevelCssClass($model['level']),
            ])
        ];
    }

    protected function messageColumn(): array
    {
        return [
            'label' => Yii::t('yii', 'Error'),
            'content' => function ($model) {
                $html = Html::tag('div', Html::encode(trim((string)$model['message'])), ['class' => 'strong']);

                if (isset($model['category'])) {
                    $html .= Html::tag('div', Html::encode($model['category']), ['class' => 'small']);
                }

                if (isset($model['vars'])) {
                    $html .= Html::tag('pre', Html::encode(rtrim((string)$model['vars'])), ['class' => 'small']);
                }

                return $html;
            }
        ];
    }

    protected function getLevelCssClass(string $level): string
    {
        return "badge badge-$level";
    }
}
