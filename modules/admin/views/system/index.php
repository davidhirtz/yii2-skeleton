<?php
/**
 * Assets list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \yii\data\ArrayDataProvider $assets
 * @var \yii\data\ArrayDataProvider $caches
 * @var \yii\data\ArrayDataProvider $logs
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use rmrevin\yii\fontawesome\FAS;

$this->setPageTitle(Yii::t('app', 'System'));
$this->setBreadcrumb($this->title);
?>

<?= Submenu::widget([
	'title'=>Yii::t('app', 'Assets'),
]); ?>

<?= Panel::widget([
	'content'=>GridView::widget([
		'dataProvider'=>$assets,
		'layout'=>'{items}{footer}',
		'columns'=>[
			[
				'label'=>Yii::t('app', 'Name'),
				'content'=>function($item)
				{
					return Html::tag('div', ucwords(str_replace('-', ' ', $item['name'])), ['class'=>'strong']).
						Html::tag('div', $item['directory'] ?: Yii::t('app', 'Unpublished'), ['class'=>'small']);
				}
			],
		],
		'footer'=>[
			[
				[
					'content'=>Html::a(Yii::t('app', 'Refresh'), ['publish'], ['class'=>'btn btn-secondary', 'data-method'=>'post']),
					'options'=>['class'=>'col text-right'],
				]
			],
		],
	]),
]); ?>

<?= Submenu::widget([
	'title'=>Yii::t('app', 'Cache'),
]); ?>

<?= Panel::widget([
	'content'=>GridView::widget([
		'dataProvider'=>$caches,
		'layout'=>'{items}{footer}',
		'columns'=>[
			[
				'label'=>Yii::t('app', 'Name'),
				'content'=>function($item)
				{
					return Html::tag('div', ucwords($item['name']), ['class'=>'strong']).
						Html::tag('div', $item['class'], ['class'=>'small']);
				}
			],
			[
				'contentOptions'=>['class'=>'text-right'],
				'content'=>function($item)
				{
					return Html::buttons(Html::a(FAS::icon('sync-alt'), ['flush', 'cache'=>$item['name']], [
						'class'=>'btn btn-secondary',
						'data-method'=>'post',
					]));
				}
			],
		],
	]),
]); ?>

<?= Submenu::widget([
	'title'=>Yii::t('app', 'Logs'),
]); ?>

<?= Panel::widget([
	'content'=>GridView::widget([
		'dataProvider'=>$logs,
		'layout'=>'{items}{footer}',
		'columns'=>[
			[
				'label'=>Yii::t('app', 'Name'),
				'content'=>function($modified, $name)
				{
					return Html::tag('div', Html::a($name, ['view', 'log'=>$name]), ['class'=>'strong']).
						Html::tag('div', Yii::t('app', 'Last updated {timestamp}.', [
							'timestamp'=>\davidhirtz\yii2\timeago\Timeago::tag($modified),
						]), ['class'=>'small']);
				}
			],
			[
				'contentOptions'=>['class'=>'text-right'],
				'content'=>function($modified, $name)
				{
					return Html::buttons(Html::a(FAS::icon('trash'), ['delete', 'log'=>$name], [
						'class'=>'btn btn-secondary',
						'data-method'=>'post',
					]));
				}
			],
		],
	]),
]); ?>
