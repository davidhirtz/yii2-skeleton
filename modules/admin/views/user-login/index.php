<?php
/**
 * Login list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 * @var string $ip
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;

$this->setPageTitle(Yii::t('app', 'Logins'));

$this->setBreadcrumb(Yii::t('app', 'Users'), ['/admin/user/index']);
$this->setBreadcrumb($this->title);
?>

<?= UserSubmenu::widget([
    'title' => Html::a(Html::encode(Yii::t('app', 'Logins')), ['/admin/user/index']),
]); ?>

<?= Panel::widget([
    'content' => UserLoginGridView::widget([
        'dataProvider' => $provider,
        'search' => $ip,
    ]),
]); ?>