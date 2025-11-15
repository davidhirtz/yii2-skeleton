<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 *
 * @var View $this
 * @var ArrayDataProvider $caches
 * @var ArrayDataProvider $logs
 * @var ArrayDataProvider $sessions
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AssetGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\CacheGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
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
                        ->addHtml(
                            Button::make()
                                ->primary()
                                ->href(['view', 'log' => $name, 'raw' => 1])
                                ->icon('file'),
                            Button::make()
                                ->danger()
                                ->icon('trash')
                                ->post(['delete', 'log' => $name])
                        )
                        ->render(),
                ],
            ],
        ]),
    ]);
}

echo Submenu::widget([
    'title' => Yii::t('skeleton', 'Assets'),
]);

echo GridContainer::make()
    ->grid(AssetGridView::make());

echo Submenu::widget([
    'title' => Yii::t('skeleton', 'Cache'),
]);

echo GridContainer::make()
    ->grid(CacheGridView::make());

echo Submenu::widget([
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
                'content' => fn (array $item): string => Button::make()
                    ->primary()
                    ->icon('trash')
                    ->post(['/admin/system/session-gc'])
                    ->tooltip(Yii::t('skeleton', 'Delete expired sessions'))
                    ->render()
            ],
        ],
    ]),
]); ?>
