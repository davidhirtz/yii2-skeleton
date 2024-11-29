<?php
declare(strict_types=1);

/**
 * @see UserController::actionCreate()
 *
 * @var View $this
 * @var UserForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'Create New User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);
?>

<?= UserSubmenu::widget([
    'user' => $form->user,
]); ?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'The user could not be created'),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $form,
    ]),
]);
?>
