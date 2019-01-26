<?php
/**
 * Recover password form.
 * @see davidhirtz\yii2\skeleton\module\admin\controllers\UserController::actionRecover()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var app\models\forms\user\LoginForm $model
 * @var yii\bootstrap4\ActiveForm $form
 */
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use rmrevin\yii\fontawesome\FA;
use yii\bootstrap4\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

$this->setPageTitle(Yii::t('app', 'Recover Password'));
$this->setBreadcrumb($this->title);
?>
<?= Html::errorSummary($model, ['header'=>Yii::t('app', 'Your password could not be reset:')]); ?>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-4">
			<?php Panel::begin(['title'=>$this->title]); ?>
			<p><?= Yii::t('app', 'Enter your email address and we will send you instructions how to reset your password.'); ?></p>
			<?php
			$form=ActiveForm::begin([
				'fieldClass'=>'davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField',
				'enableClientValidation'=>false,
			]);
			?>
			<?= $form->field($model, 'email', ['inputOptions'=>['type'=>'email', 'autofocus'=>!$model->hasErrors()], 'icon'=>'envelope', 'enableError'=>false]); ?>
			<div class="form-group">
				<?= Html::submitButton(Yii::t('app', 'Send Email'), ['class'=>'btn btn-primary btn-block']) ?>
			</div>
			<?php ActiveForm::end(); ?>
			<?php Panel::end(); ?>
		</div>
	</div>
	<?php
	if(Yii::$app->user->isGuest)
	{
		?>
		<div class="row justify-content-center">
			<div class="col-md-4">
				<div class="list-group">
					<a href="<?php echo Url::to(['login']); ?>" class="list-group-item">
						<?= FA::icon('sign-in', ['class'=>'fa-fw icon-left']); ?><?= Yii::t('app', 'Back to login'); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
	?>
</div>
