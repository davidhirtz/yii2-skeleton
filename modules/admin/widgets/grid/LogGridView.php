<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use Yii;

/**
 * Class LogGridView
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 *
 * @property LogDataProvider $dataProvider
 */
class LogGridView extends GridView
{

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
    public function init()
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
            'content' => function ($model) {
                return Yii::$app->getFormatter()->asDatetime($model['date'], 'short');
            }
        ];
    }

    public function levelColumn()
    {
        return [
            'label' => Yii::t('skeleton', 'Level'),
            'headerOptions' => ['width' => '100'],
            'content' => function ($model) {
                return Html::tag('div', $model['level'], ['class' => $this->getLevelCssClass($model['level'])]);
            }
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
                $html = Html::tag('div', trim($model['message']), ['class' => 'strong']);

                if (isset($model['category'])) {
                    $html .= Html::tag('div', $model['category'], ['class' => 'small']);
                }

                if (isset($model['vars'])) {
                    $html .= Html::tag('pre', Html::encode(trim($model['vars'])), ['class' => 'small']);
                }

                return $html;
            }
        ];
    }

    /**
     * @param string $level
     * @return string
     */
    protected function getLevelCssClass($level)
    {
        return 'btn btn-sm ' . ($level !== 'error' ? "bg-{$level}" : 'btn-danger');
    }
}