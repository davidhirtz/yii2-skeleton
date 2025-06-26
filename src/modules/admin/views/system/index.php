<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 *
 * @var View $this
 * @var ArrayDataProvider $assets
 * @var ArrayDataProvider $caches
 * @var ArrayDataProvider $logs
 * @var ArrayDataProvider $sessions
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\ButtonToolbar;
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
                    'content' => fn ($modified, $name): string => ButtonToolbar::make()
                        ->buttons(
                            Button::primary()
                                ->href(['view', 'log' => $name, 'raw' => 1])
                                ->icon('file'),
                            Button::danger()
                                ->icon('trash')
                                ->post(['delete', 'log' => $name])
                        )
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
                    'content' => Button::primary(Yii::t('skeleton', 'Refresh'))
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
                'content' => fn (array $item): string => Button::primary()
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

<?= Panel::widget([
    'content' => GridView::widget([
        'dataProvider' => $sessions,
        'layout' => '{items}{footer}',
        'columns' => [
            [
                'label' => Yii::t('skeleton', 'Sessions'),
                'content' => function ($item): string {
                    $title = Yii::t('skeleton', 'Expired sessions: {count,number}', [
                        'count' => $item['expiredSessionCount'],
                    ]);
                    $content = Yii::t('app', 'Total sessions: {sessionCount,number} / Garbage collection probability: {probability}', [
                        'sessionCount' => $item['sessionCount'],
                        'probability' => Yii::$app->getSession()->getGCProbability(),
                    ]);

                    return Html::tag('div', $title, ['class' => 'strong']) . Html::tag('div', $content, ['class' => 'small']);
                }
            ],
            [
                'contentOptions' => ['class' => 'text-end'],
                /** @see SystemController::actionSessionGc() */
                'content' => fn (array $item): string => Button::primary()
                    ->icon('trash')
                    ->post(['/admin/system/session-gc'])
                    ->tooltip(Yii::t('skeleton', 'Delete expired sessions'))
                    ->render()
            ],
        ],
    ]),
]); ?>
