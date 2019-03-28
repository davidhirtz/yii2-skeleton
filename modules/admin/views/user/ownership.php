<?php
/**
 * Transfer ownership form.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionOwnership()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\skeleton\models\forms\OwnershipForm $form
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;

$this->setTitle(Yii::t('skeleton', 'Transfer Ownership'));
?>

<?= UserSubmenu::widget(); ?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'The site ownership could not be transferred'),
]); ?>

<?php
Panel::begin([
    'title' => $this->title,
    'type' => 'danger',
]);

$af = ActiveForm::begin();

echo $af->textRow(Yii::t('skeleton', 'Enter the username of the user you want to make owner of this site. This will remove all your admin privileges and there is no going back. Please be certain!'));
echo $af->field($form, 'name');
echo $af->buttonRow($af->button(Yii::t('skeleton', 'Transfer'), ['class' => 'btn-danger']));

ActiveForm::end();
Panel::end(); ?>
