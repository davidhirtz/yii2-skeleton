<?php
/**
 * Login form.
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $model
 * @var yii\bootstrap4\ActiveForm $form
 */
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use app\models\User;
use rmrevin\yii\fontawesome\FA;
use yii\bootstrap4\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

$this->setPageTitle(Yii::t('app', 'Login'));
$this->setBreadcrumb($this->title);
?>
<?= Html::errorSummary($model, [
	'header'=>Yii::t('app', 'Login unsuccessful:'),
]); ?>
<noscript>
	<div class="alert alert-danger">
		<p class="lead"><?php echo Yii::t('app', 'JavaScript is disabled on your browser.'); ?></p>
		<p><?php echo Yii::t('app', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
	</div>
</noscript>
<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-4">
			<?= $this->render('_auth'); ?>
		</div>
	</div>
	<div class="row justify-content-center mb-2">
		<div class="col-md-4">
			<?php Panel::begin(['title'=>$this->title]); ?>
			<?php
			$form=ActiveForm::begin([
				'fieldClass'=>'davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField',
				'enableClientValidation'=>false,
			]);

			echo $form->field($model, 'email', [
				'inputOptions'=>[
					'type'=>'email',
					'autofocus'=>!$model->hasErrors()],
				'icon'=>'envelope',
				'enableError'=>false,
			]);

			echo $form->field($model, 'password', ['icon'=>'key', 'enableError'=>false])->passwordInput();

			if(Yii::$app->getUser()->enableAutoLogin)
			{
				echo $form->field($model, 'rememberMe')->checkbox();
			}
			?>
			<div class="form-group">
				<?= Html::submitButton(Yii::t('app', 'Login'), ['class'=>'btn btn-primary btn-block']) ?>
			</div>
			<?php $form->end(); ?>
			<?php Panel::end(); ?>
		</div>
	</div>
	<div class="row justify-content-center">
		<div class="col-md-4">
			<div class="list-group">
				<?php
				if(Yii::$app->params['user.enableSignup'] || !User::find()->count())
				{
					?>
					<a href="<?php echo Url::to(['create']); ?>" class="list-group-item list-group-item-action">
						<?= FA::icon('user', ['class'=>'fa-fw icon-left']); ?><?= Yii::t('app', 'Create new account'); ?>
					</a>
					<?php
				}

				if(Yii::$app->params['user.resetPassword'])
				{
					if(!Yii::$app->params['user.unconfirmedLogin'])
					{
						?>
						<a href="<?php echo Url::to(['resend']); ?>" class="list-group-item list-group-item-action">
							<?= FA::icon('envelope', ['class'=>'fa-fw icon-left']); ?><?= Yii::t('app', 'Resend email confirmation'); ?>
						</a>
						<?php
					}
					?>
					<a href="<?php echo Url::to(['recover']); ?>" class="list-group-item list-group-item-action">
						<?= FA::icon('key', ['class'=>'fa-fw icon-left']); ?><?= Yii::t('app', 'I forgot my password'); ?>
					</a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>