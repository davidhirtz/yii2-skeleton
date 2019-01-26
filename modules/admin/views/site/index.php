<?php
/**
 * Admin index.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SiteController::actionIndex()
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var HomePanelInterface[] $panels
 */
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HomePanel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HomePanelInterface;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ListGroup;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setPageTitle(Yii::t('app', 'Admin'));
?>
<h1 class="page-header"><?= Yii::$app->name; ?></h1>
<div class="row justify-content-center">
	<div class="col-12 col-md-6 col-lg-4">
		<?= Panel::widget([
			'title'=>HomePanel::getTitle(),
			'content'=>ListGroup::widget([
				'items'=>HomePanel::getListItems(),
			]),
		]); ?>
	</div>
	<?php
	foreach($panels as $panel)
	{
		?>
		<div class="col-12 col-md-6 col-lg-4">
			<?= Panel::widget([
				'title'=>$panel::getTitle(),
				'content'=>ListGroup::widget([
					'items'=>$panel::getListItems(),
				]),
			]); ?>
		</div>
		<?php
	}
	?>

</div>
