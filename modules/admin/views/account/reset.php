<?php
/**
 * Reset password form.
 * @see \davidhirtz\yii2\skeleton\module\admin\controllers\AccountController::actionReset()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \app\models\forms\user\PasswordResetForm $model
 * @var \yii\bootstrap4\ActiveForm $form
 */
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\bootstrap4\ActiveForm;

$this->setPageTitle(Yii::t('app', 'Set New Password'));
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($model, [
	'header'=>Yii::t('app', 'Your password could not be saved:'),
]); ?>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-4">
			<?php Panel::begin(['title'=>$this->title]); ?>
			<p><?= Yii::t('app', 'Please enter a new password below to update your account.'); ?></p>
			<?php
			$form=ActiveForm::begin([
				'fieldClass'=>'davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField',
				'enableClientValidation'=>false,
			]);

			echo $form->field($model, 'name', ['inputOptions'=>['readonly'=>true], 'icon'=>'user']);
			echo $form->field($model, 'newPassword', ['inputOptions'=>['autofocus'=>!$model->hasErrors()], 'icon'=>'key'])->passwordInput();
			echo $form->field($model, 'repeatPassword', ['icon'=>'key'])->passwordInput();
			?>
			<div class="form-group">
				<?= Html::submitButton(Yii::t('app', 'Save New Password'), ['class'=>'btn btn-primary btn-block']) ?>
			</div>
			<?php $form->end(); ?>
			<?php Panel::end(); ?>
		</div>
	</div>
</div>