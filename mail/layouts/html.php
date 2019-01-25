<?php
/**
 * Email layout.
 *
 * @var yii\web\View $this
 * @var string $content
 */
use yii\helpers\Html;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<style type="text/css">
	*{
		-webkit-box-sizing:border-box;
		-moz-box-sizing:border-box;
		box-sizing:border-box;
	}

	body{
		margin:0;
		padding:0;
		font:14px/1.4 "Helvetica Neue", Helvetica, Arial, sans-serif;
		color:#333;
		background-color:#f9f9f9;
	}

	.wrap{
		margin:40px auto;
		padding:20px;
		max-width:600px;
		background-color:#fff;
	}

	a {
		color: #365899;
		text-decoration: none;
	}

	a:hover, a:focus {
		color: #000;
		text-decoration: underline;
	}

	p{
		margin:0;
	}

	p:not(:last-child){
		margin-bottom:1em;
	}

	table {
		margin:0 0 1em;
		background-color: transparent;
		border-collapse: collapse;
		border-spacing: 0;
	}

	td {
		padding: 0;
	}

	td:first-child{
		padding-right:1em;
	}

	.btn-wrap{
		margin-top:2em;
		text-align:center;
	}

	.btn {
		display: inline-block;
		margin-bottom: 0;
		font-size: inherit;
		font-weight: normal;
		text-align: center;
		vertical-align: middle;
		touch-action: manipulation;
		cursor: pointer;
		background-image: none;
		border: 1px solid transparent;
		white-space: nowrap;
		padding: 6px 12px;
		line-height: 1.42857;
		border-radius: 3px;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}
	.btn-primary:hover {
		color: #fff;
		background-color: #090909;
		border-color: #000;
	}

	.btn-primary {
		color: #fff;
		background-color: #222;
		border-color: #151515;
	}

</style>
<div class="wrap">
    <?= $content ?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
