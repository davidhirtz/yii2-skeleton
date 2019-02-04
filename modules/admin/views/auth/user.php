<?php
/**
 * User auth list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionView()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */

use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use rmrevin\yii\fontawesome\FAS;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

$this->setPageTitle(Yii::t('app', 'Edit Permissions'));

$this->setBreadcrumb(Yii::t('app', 'Users'), ['/admin/user/index']);
$this->setBreadcrumb($user->getUsername(), ['/admin/user/update', 'id' => $user->id]);
$this->setBreadcrumb($this->title);

/**
 * Grid.
 */
$grid = new GridView([
    'dataProvider' => $provider,
    'tableOptions' => [
        'class' => 'table',
    ],
    'rowOptions' => function (AuthItem $authItem) {
        return ($authItem->isAssigned || $authItem->isInherited) ? ['class' => 'bg-success'] : null;
    },
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
                $items = [Yii::t('app', $authItem->description)];

                foreach ($authItem->children as $child) {
                    $items[] = !$authItem->isAssigned && ($child->isAssigned || $child->isInherited) ? Html::tag('span', Yii::t('app', $child->description), ['class' => 'bg-success']) : Yii::t('app', $child->description);
                }

                return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
            }
        ],
        [
            'contentOptions' => ['class' => 'text-right'],
            'content' => function (AuthItem $authItem) use ($user) {
                return Html::a(FAS::icon($authItem->isAssigned ? 'ban' : 'star'), [
                    $authItem->isAssigned ? 'revoke' : 'assign',
                    'id' => $user->id,
                    'name' => $authItem->name,
                    'type' => $authItem->type
                ], [
                    'class' => 'btn btn-secondary',
                    'data-method' => 'post',
                ]);
            }
        ],
    ],
]);
?>
<h1 class="page-header"><?= Html::a(Html::encode($user->getUsername()), [
        '/admin/user/update',
        'id' => $user->id
    ]); ?></h1>
<?= UserSubmenu::widget(['user' => $user]); ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?= Html::encode($this->title); ?>
        </h2>
    </div>
    <div class="card-body">
        <div class="alert alert-dismissible alert-info">
            <a class="close" href="<?= Url::to(['index']); ?>" aria-label="Close">
                <span aria-hidden="true">×</span>
            </a>
            <?php echo Yii::t('app', 'Assign and revoke user permissions to {name}. Click {here} to view all permissions.', [
                'name' => $user->getUsername(),
                'here' => Html::a(Yii::t('app', 'here'), ['index'], ['class' => 'alert-link']),
            ]); ?>
        </div>
        <?php
        Pjax::begin([
            'enablePushState' => false,
        ]);

        ActiveForm::begin([
            'options' => [
                'data-pjax' => true,
            ],
        ]);

        echo $grid->renderItems();

        ActiveForm::end();
        Pjax::end();
        ?>
    </div>
</div>