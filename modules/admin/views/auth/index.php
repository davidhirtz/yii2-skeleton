<?php
/**
 * Admin auth list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->setTitle(Yii::t('skeleton', 'Permissions'));
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => GridView::widget([
        'dataProvider' => $provider,
        'summaryOptions' => [
            'class' => 'summary alert alert-info',
        ],
        'tableOptions' => [
            'class' => 'table table-striped',
        ],
        'columns' => [
            [
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
                'content' => function (AuthItem $authItem) {
                    return Icon::tag($authItem->getTypeIcon(), [
                        'data-toggle' => 'tooltip',
                        'title' => $authItem->getTypeName()
                    ]);
                }
            ],
            [
                'attribute' => 'displayName',
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            ],
            [
                'attribute' => 'description',
                'content' => function (AuthItem $authItem) {
                    $items = [Yii::t('skeleton', $authItem->description)];

                    foreach ($authItem->children as $child) {
                        $items[] = Yii::t('skeleton', $child->description);
                    }

                    return Html::ul(array_filter($items), ['class' => 'list-unstyled']);
                }
            ],
            [
                'label' => Yii::t('skeleton', 'Users'),
                'content' => function (AuthItem $authItem) {
                    $items = [];

                    foreach ($authItem->users as $user) {
                        $items[$user->id] = Html::a($user->getUsername(), ['auth/view', 'user' => $user->id]);
                    }

                    return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
                }
            ],
        ],
    ]),
]); ?>