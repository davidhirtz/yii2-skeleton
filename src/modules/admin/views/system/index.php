<?php

declare(strict_types=1);

/**
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
use davidhirtz\yii2\skeleton\html\Btn;
use davidhirtz\yii2\skeleton\html\BtnToolbar;
use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use davidhirtz\yii2\timeago\Timeago;
use yii\data\ArrayDataProvider;

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
                    'contentOptions' => ['class' => 'text-end'],
                    'content' => fn ($modified, $name): string => BtnToolbar::tag()
                        ->addButton(Btn::primary()
                            ->href(['view', 'log' => $name, 'raw' => 1])
                            ->icon('file'))
                        ->addButton(Btn::danger()
                            ->icon('trash')
                            ->post(['delete', 'log' => $name]))
                        ->render(),
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
                    /** @see SystemController::actionPublish() */
                    'content' => Btn::primary(Yii::t('skeleton', 'Refresh'))
                        ->icon('sync-alt')
                        ->post(['publish']),
                    'options' => ['class' => 'ms-auto'],
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
                'content' => fn ($item): string => Html::tag('div', ucwords((string)$item['name']), ['class' => 'strong']) .
                    Html::tag('div', $item['class'], ['class' => 'small'])
            ],
            [
                'contentOptions' => ['class' => 'text-end'],
                /** @see SystemController::actionFlush() */
                'content' => fn (array $item): string => Btn::primary()
                    ->icon('sync-alt')
                    ->post(['flush', 'cache' => $item['name']])
                    ->render()
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
            <table class="table table-striped table-hover">
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
                    <td class="text-end">
                        <?= Btn::primary()
                            ->icon('trash')
                            ->post(['/admin/system/session-gc'])
                            ->tooltip(Yii::t('skeleton', 'Delete expired sessions'))
                            ->render(); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
