<?php
/**
 * Recover password form.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionRecover()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 * @var yii\bootstrap4\ActiveForm $af
 */
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use rmrevin\yii\fontawesome\FAS;
use yii\helpers\Url;

$this->setPageTitle(Yii::t('app', 'Recover Password'));
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($form, ['header'=>Yii::t('app', 'Your password could not be reset:')]); ?>

<div class="container">
	<div class="centered">
		<?php Panel::begin(['title'=>$this->title]); ?>
		<p><?= Yii::t('app', 'Enter your email address and we will send you instructions how to reset your password.'); ?></p>
		<?php
		$af=ActiveForm::begin([
			'enableClientValidation'=>false,
		]);
		?>
		<?= $af->field($form, 'email', ['icon'=>'envelope', 'enableError'=>false])->textInput([
			'type'=>'email',
			'autofocus'=>!$form->hasErrors(),
		]); ?>
		<div class="form-group">
			<?= Html::submitButton(Yii::t('app', 'Send Email'), ['class'=>'btn btn-primary btn-block']) ?>
		</div>
		<?php ActiveForm::end(); ?>
		<?php Panel::end(); ?>
		<?php
		if(Yii::$app->getUser()->getIsGuest())
		{
			?>
			<div class="list-group">
				<a href="<?php echo Url::to(['login']); ?>" class="list-group-item">
					<?= FAS::icon('sign-in-alt', ['class'=>'fa-fw icon-left']); ?><?= Yii::t('app', 'Back to login'); ?>
				</a>
			</div>
			<?php
		}
		?>
	</div>
</div>
