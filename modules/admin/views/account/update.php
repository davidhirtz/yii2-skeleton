<?php
/**
 * Edit account form.
 * @see \davidhirtz\yii2\skeleton\module\admin\controllers\AccountController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \app\models\forms\user\AccountForm $user
 * @var \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm $form
 */
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\UserActiveForm;

$this->setPageTitle(Yii::t('app', 'Account'));
$this->setBreadcrumb($this->title);
?>
<?php
if($user->getIsUnconfirmed())
{
	?>
	<div class="alert alert-warning">
		<?php
		echo Yii::t('app', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
			'email'=>$user->email,
			'here'=>Html::a(Yii::t('app', 'here'), ['resend']),
		]);
		?>
	</div>
	<?php
}
?>

<?= Html::errorSummary($user, [
	'header'=>Yii::t('app', 'Your account could not be updated:'),
]); ?>

<?= Panel::widget([
	'title'=>$this->title,
	'content'=>UserActiveForm::widget([
		'user'=>$user,
	]),
]);
?>

<?php Panel::begin([
	'title'=>Yii::t('app', 'Clients'),
]) ?>
	<?php
	if($user->authClients)
	{
	?>
		<?= $this->render('_clients', ['user'=>$user]); ?>
		<hr>
	<?php
	}
	?>
	<p>
		<?= Yii::t('app', 'Click {here} to add {clientCount, plural, =0{an external client} other{additional clients}} to your account.', [
			'clientCount'=>count($user->authClients),
			'here'=>Html::a(Yii::t('app', 'here'), '#', ['data-toggle'=>'modal', 'data-target'=>'#auth-client-modal']),
		]); ?>
	</p>
<?php Panel::end(); ?>
<div class="modal fade" id="auth-client-modal" tabindex="-1" role="dialog" aria-labelledby="resize-modal-label">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= Yii::t('app', 'Close'); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">
					<?= Yii::t('app', 'Clients'); ?>
				</h4>
			</div>
			<div class="modal-body">
				<?= $this->render('_auth'); ?>
			</div>
		</div>
	</div>
</div>

<?php
if(!$user->getIsOwner())
{
	echo Panel::widget([
		'type'=>'danger',
		'title'=>Yii::t('app', 'Delete Account'),
		'content'=>DeleteActiveForm::widget([
			'model'=>$user,
			'attribute'=>'name',
			'action'=>['delete'],
			'message'=>Yii::t('app', 'Type your username in the text field below to delete your account, all related items and uploaded files. This cannot be undone, please be certain!'),
		])
	]);
}
?>