<?php
/**
 * Clients list.
 * @see \davidhirtz\yii2\skeleton\module\admin\controllers\AccountController::actionUpdate()
 * @see \app\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \app\models\forms\user\AccountForm $user
 */
use yii\helpers\Html;
use yii\helpers\Url;
?>
<table class="table table-vertical table-striped">
	<thead>
		<tr>
			<th><?= Yii::t('app', 'Client'); ?></th>
			<th><?= Yii::t('app', 'Name'); ?></th>
			<th class="d-none d-table-cell-md"><?= Yii::t('app', 'Updated'); ?></th>
			<th class="d-none d-table-cell-lg"><?= Yii::t('app', 'Created'); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($user->authClients as $auth)
	{
		$client=$auth->getClientClass();
		$url=$client::getExternalUrl($auth);
		$title=$client->getTitle();
		?>
		<tr>
			<td><?= $title; ?></td>
			<td><?= $url ? Html::a($auth->getDisplayName(), $url, ['target'=>'_blank']) : $auth->getDisplayName(); ?></td>
			<td class="d-none d-table-cell-md"><?= \davidhirtz\yii2\timeago\Timeago::tag($auth->updated_at); ?>
			<td class="d-none d-table-cell-lg"><?= \davidhirtz\yii2\timeago\Timeago::tag($auth->created_at); ?>
			<td class="text-right">
				<a href="<?= Url::to(['deauthorize', 'id'=>$auth->id, 'name'=>$auth->name]) ?>" data-method="post" data-confirm="<?= Yii::t('app', 'Are you sure your want to remove {isOwner, select, 1{your} other{this}} {client} account?', ['client'=>$title, 'isOwner'=>$auth->user_id==Yii::$app->user->id]); ?>" data-toggle="tooltip" title="<?= Yii::t('app', 'Remove {client}', ['client'=>$title]); ?>" class="btn btn-danger">
					<i class="fa fa-remove"></i>
				</a>
			</td>
		</tr>
	<?php
	}
	?>
	</tbody>
</table>