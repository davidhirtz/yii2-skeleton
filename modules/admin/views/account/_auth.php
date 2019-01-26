<?php
/**
 * Login client links.
 * @see davidhirtz\yii2\skeleton\module\admin\controllers\UserController::actionCreate()
 * @see davidhirtz\yii2\skeleton\module\admin\controllers\UserController::actionLogin()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 */
use rmrevin\yii\fontawesome\FA;
use yii\helpers\Url;
?>
<div class="list-group">
<?php
if(Yii::$app->params['facebook.appId'] && Yii::$app->params['facebook.secret'])
{
	?>
	<a href="<?php echo Url::to(['auth', 'client'=>'facebook']); ?>" class="list-group-item">
		<?= FA::icon('facebook', ['class'=>'fa-fw']); ?>
		<?= Yii::t('app', 'Login with Facebook'); ?>
	</a>
<?php
}
?>
</div>