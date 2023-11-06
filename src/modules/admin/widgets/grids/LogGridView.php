<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use Yii;

/**
 * @property LogDataProvider $dataProvider
 */
class LogGridView extends GridView
{
    /**
     * @var string
     */
    public $layout = '{items}';

    /**
     * @var array containing the table options
     */
    public $tableOptions = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->columns = [
            $this->dateColumn(),
            $this->levelColumn(),
            $this->messageColumn(),
        ];

        $this->getView()->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::init();
    }

    /**
     * @return array
     */
    public function dateColumn()
    {
        return [
            'label' => Yii::t('skeleton', 'Date'),
            'headerOptions' => ['width' => '150'],
            'contentOptions' => ['class' => 'text-nowrap'],
            'content' => fn($model) => Yii::$app->getFormatter()->asDatetime(new DateTime($model['date']), 'short')
        ];
    }

    /**
     * @return array
     */
    public function levelColumn()
    {
        return [
            'label' => Yii::t('skeleton', 'Level'),
            'headerOptions' => ['width' => '100'],
            'content' => fn($model) => Html::tag('div', $model['level'], ['class' => $this->getLevelCssClass($model['level'])])
        ];
    }

    /**
     * @return array
     */
    public function messageColumn()
    {
        return [
            'label' => Yii::t('yii', 'Error'),
            'content' => function ($model) {
                $html = Html::tag('div', trim((string) $model['message']), ['class' => 'strong']);

                if (isset($model['category'])) {
                    $html .= Html::tag('div', $model['category'], ['class' => 'small']);
                }

                if (isset($model['vars'])) {
                    $html .= Html::tag('pre', Html::encode(rtrim((string) $model['vars'])), ['class' => 'small']);
                }

                return $html;
            }
        ];
    }

    /**
     * @param string $level
     * @return string
     */
    protected function getLevelCssClass($level): string
    {
        return 'btn btn-sm ' . ($level !== 'error' ? "bg-{$level}" : 'btn-danger');
    }
}