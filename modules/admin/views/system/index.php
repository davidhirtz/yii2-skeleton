<?php
/**
 * Assets list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ArrayDataProvider $assets
 * @var \yii\data\ArrayDataProvider $caches
 * @var \yii\data\ArrayDataProvider $logs
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use rmrevin\yii\fontawesome\FAS;

$this->setTitle(Yii::t('skeleton', 'System'));
?>

<?= Submenu::widget([
    'title' => Yii::t('skeleton', 'Assets'),
]); ?>

<?= Panel::widget([
    'content' => GridView::widget([
        'dataProvider' => $assets,
        'layout' => '{items}{footer}',
        'columns' => [
            [
                'label' => Yii::t('skeleton', 'Name'),
                'content' => function ($item) {

                    $links = [];

                    foreach ($item['files'] as $file => $link) {
                        $links[] = Html::a($file, $link, ['target' => '_blank']);
                    }

                    return Html::tag('div', $item['name'], ['class' => 'strong']) .
                        Html::ul($links, ['class' => 'small', 'encode' => false]);
                }
            ],
            [
                'label' => Yii::t('skeleton', 'Updated'),
                'contentOptions' => ['style' => 'vertical-align:top'],
                'content' => function ($item) {
                    return \davidhirtz\yii2\timeago\Timeago::tag($item['modified']);
                }
            ]
        ],
        'footer' => [
            [
                [
                    'content' => Html::a(Yii::t('skeleton', 'Refresh'), ['publish'], [
                        'class' => 'btn btn-secondary',
                        'data-method' => 'post'
                    ]),
                    'options' => ['class' => 'col text-right'],
                ]
            ],
        ],
    ]),
]); ?>

<?= Submenu::widget([
    'title' => Yii::t('skeleton', 'Cache'),
]); ?>

<?= Panel::widget([
    'content' => GridView::widget([
        'dataProvider' => $caches,
        'layout' => '{items}{footer}',
        'columns' => [
            [
                'label' => Yii::t('skeleton', 'Name'),
                'content' => function ($item) {
                    return Html::tag('div', ucwords($item['name']), ['class' => 'strong']) .
                        Html::tag('div', $item['class'], ['class' => 'small']);
                }
            ],
            [
                'contentOptions' => ['class' => 'text-right'],
                'content' => function ($item) {
                    return Html::buttons(Html::a(FAS::icon('sync-alt'), ['flush', 'cache' => $item['name']], [
                        'class' => 'btn btn-secondary',
                        'data-method' => 'post',
                    ]));
                }
            ],
        ],
    ]),
]); ?>

<?= Submenu::widget([
    'title' => Yii::t('skeleton', 'Logs'),
]); ?>

<?= Panel::widget([
    'content' => GridView::widget([
        'dataProvider' => $logs,
        'layout' => '{items}{footer}',
        'columns' => [
            [
                'label' => Yii::t('skeleton', 'Name'),
                'content' => function ($modified, $name) {
                    return Html::tag('div', Html::a($name, ['view', 'log' => $name]), ['class' => 'strong']) .
                        Html::tag('div', Yii::t('skeleton', 'Last updated {timestamp}.', [
                            'timestamp' => \davidhirtz\yii2\timeago\Timeago::tag($modified),
                        ]), ['class' => 'small']);
                }
            ],
            [
                'contentOptions' => ['class' => 'text-right'],
                'content' => function ($modified, $name) {
                    return Html::buttons(Html::a(FAS::icon('trash'), ['delete', 'log' => $name], [
                        'class' => 'btn btn-secondary',
                        'data-method' => 'post',
                    ]));
                }
            ],
        ],
    ]),
]); ?>
