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
use rmrevin\yii\fontawesome\FAS;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->setPageTitle(Yii::t('skeleton', 'Permissions'));

$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
$this->setBreadcrumb($this->title);
?>

<?= UserSubmenu::widget([
    'title' => Html::a(Html::encode(Yii::t('skeleton', 'Permissions')), ['/admin/user/index']),
]); ?>

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
                'headerOptions' => ['class' => 'hidden-sm hidden-xs'],
                'contentOptions' => ['class' => 'text-center hidden-sm hidden-xs'],
                'content' => function (AuthItem $authItem) {
                    return FAS::icon($authItem->getTypeIcon(), [
                        'data-toggle' => 'tooltip',
                        'title' => $authItem->getTypeName()
                    ]);
                }
            ],
            [
                'attribute' => 'displayName',
                'headerOptions' => ['class' => 'hidden-xs'],
                'contentOptions' => ['class' => 'hidden-xs'],
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
                        $items[$user->id] = Html::a($user->getUsername(), ['user', 'id' => $user->id]);
                    }

                    return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
                }
            ],
        ],
    ]),
]); ?>