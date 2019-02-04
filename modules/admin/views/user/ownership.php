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

$this->setPageTitle(Yii::t('app', 'Transfer Ownership'));

$this->setBreadcrumb(Yii::t('app', 'Users'), ['index']);
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($form, [
	'header'=>Yii::t('app', 'The site ownership could not be transferred'),
]); ?>

<?= UserSubmenu::widget([
	'title'=>Html::a(Html::encode(Yii::t('app', 'Users')), ['index']),
]); ?>

<?php
Panel::begin([
	'title'=>$this->title,
	'type'=>'danger',
]);

$af=ActiveForm::begin();

echo $af->textRow(Yii::t('app', 'Enter the username of the user you want to make owner of this site. This will remove all your admin privileges and there is no going back. Please be certain!'));
echo $af->field($form, 'name');
echo $af->buttonRow($af->button(Yii::t('app', 'Transfer'), ['class'=>'btn-danger']));

ActiveForm::end();
Panel::end(); ?>
