<?php
/**
 * Signup form.
 * @see davidhirtz\yii2\skeleton\module\admin\controllers\UserController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var app\models\forms\user\SignupForm $user
 * @var yii\bootstrap4\ActiveForm $form
 */
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\bootstrap4\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

\app\assets\SignupAsset::register($this);

$this->setPageTitle(Yii::t('app', 'Sign up'));
$this->setBreadcrumb($this->title);
?>
<?= Html::errorSummary($user, [
	'header'=>Yii::t('app', 'Your account could not be created:'),
]); ?>
<noscript>
	<div class="alert alert-danger">
		<p><?php echo Yii::t('app', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
	</div>
</noscript>
<div class="container">

	<div class="row justify-content-center">
		<div class="col-md-4">
			<?= $this->render('_auth'); ?>
		</div>
	</div>
	<div class="row justify-content-center">
		<div class="col-md-4">
			<?php Panel::begin(['title'=>$this->title]); ?>
			<?php
			$form=ActiveForm::begin([
				'fieldClass'=>'davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField',
				'validationStateOn'=>ActiveForm::VALIDATION_STATE_ON_CONTAINER,
			]);

			$this->registerJs("jQuery('#{$form->id}').signupForm();");

			echo $form->field($user, 'name', ['inputOptions'=>['autofocus'=>!$user->hasErrors()], 'icon'=>'user']);
			echo $form->field($user, 'email', ['inputOptions'=>['type'=>'email'], 'icon'=>'envelope']);
			echo $form->field($user, 'password', ['icon'=>'key'])->passwordInput();
			echo $form->field($user, 'terms', ['enableError'=>false])->checkbox();
			?>
			<div class="form-group">
				<?php
				echo Html::activeHiddenInput($user, 'honeypot', ['id'=>'honeypot']);
				echo Html::activeHiddenInput($user, 'token', ['id'=>'token', 'data-url'=>Url::to(['token'])]);
				echo Html::activeHiddenInput($user, 'timezone', ['id'=>'tz']);
				echo Html::submitButton(Yii::t('app', 'Create Account'), ['class'=>'btn btn-primary btn-block'])
				?>
			</div>
			<?php ActiveForm::end(); ?>
			<?php Panel::end(); ?>
		</div>
	</div>
</div>