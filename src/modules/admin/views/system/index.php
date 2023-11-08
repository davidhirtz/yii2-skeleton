<?php
/**
 * Assets list.
 * @see SystemController::actionIndex()
 * @see SystemController::actionSessionGc()
 *
 * @var View $this
 * @var ArrayDataProvider $assets
 * @var ArrayDataProvider $caches
 * @var ArrayDataProvider $logs
 * @var int $sessionCount
 * @var int $expiredSessionCount
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use davidhirtz\yii2\timeago\Timeago;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'System'));
?>
<?php if ($logs->getModels()) {
    echo Submenu::widget([
        'title' => Yii::t('skeleton', 'Logs'),
    ]);

    echo Panel::widget([
        'content' => GridView::widget([
            'dataProvider' => $logs,
            'layout' => '{items}{footer}',
            'columns' => [
                [
                    'label' => Yii::t('skeleton', 'Name'),
                    'content' => function ($modified, $name): string {
                        $html = Html::tag('div', Html::a($name, ['view', 'log' => $name]), ['class' => 'strong']);
                        $html .= Html::tag('div', Yii::t('skeleton', 'Last updated {timestamp}', ['timestamp' => Timeago::tag($modified)]), ['class' => 'small']);

                        return $html;
                    }
                ],
                [
                    'contentOptions' => ['class' => 'text-right'],
                    'content' => fn ($modified, $name): string => Html::buttons([
                        Html::a(Icon::tag('file'), ['view', 'log' => $name, 'raw' => 1], [
                            'class' => 'btn btn-primary',
                        ]),
                        Html::a(Icon::tag('trash'), ['delete', 'log' => $name], [
                            'class' => 'btn btn-danger',
                            'data-method' => 'post',
                        ]),
                    ])
                ],
            ],
        ]),
    ]);
}
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
                'content' => function ($item): string {
                    $links = [];

                    foreach ($item['files'] as $file => $link) {
                        $links[] = Html::a($file, $link . $file, ['target' => '_blank']);
                    }

                    return Html::tag('div', $item['name'], ['class' => 'strong']) .
                        Html::ul($links, ['class' => 'small', 'encode' => false]);
                }
            ],
            [
                'label' => Yii::t('skeleton', 'Updated'),
                'contentOptions' => ['style' => 'vertical-align:top'],
                'content' => fn ($item): string => Timeago::tag($item['modified'])
            ]
        ],
        'footer' => [
            [
                [
                    'content' => Html::a(Yii::t('skeleton', 'Refresh'), ['publish'], [
                        'class' => 'btn btn-primary',
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
                'content' => fn ($item): string => Html::tag('div', ucwords((string) $item['name']), ['class' => 'strong']) .
                    Html::tag('div', $item['class'], ['class' => 'small'])
            ],
            [
                'contentOptions' => ['class' => 'text-right'],
                /** @see SystemController::actionFlush() */
                'content' => fn ($item): string => Html::buttons(Html::a(Icon::tag('sync-alt'), ['flush', 'cache' => $item['name']], [
                    'class' => 'btn btn-primary',
                    'data-method' => 'post',
                ]))
            ],
        ],
    ]),
]); ?>

<?= Submenu::widget([
    'title' => Yii::t('skeleton', 'Sessions'),
]); ?>

<div class="card card-default">
    <div class="card-body">
        <div class="grid-view">
            <table class="table table-vertical table-striped table-hover">
                <thead>
                <tr>
                    <th>Sessions</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <div>
                            <span class="strong"><?= Yii::t('skeleton', 'Expired sessions'); ?>: <?= $expiredSessionCount; ?></span>
                        </div>
                        <div class="small">
                            <?= Yii::t('skeleton', 'Total sessions'); ?>: <?= $sessionCount; ?> / <?= Yii::t('skeleton', 'Garbage collection probability'); ?>: <?= Yii::$app->getSession()->getGCProbability(); ?>
                        </div>
                    </td>
                    <td class="text-right">
                        <a class="btn btn-primary" href="<?= Url::toRoute(['/admin/system/session-gc']) ?>" data-method="post"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>