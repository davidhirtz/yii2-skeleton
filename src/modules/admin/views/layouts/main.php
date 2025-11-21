<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var string $content
 */

use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\NavBar;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\Flashes;
use davidhirtz\yii2\skeleton\widgets\navs\Breadcrumb;
use yii\helpers\Html;

AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->getI18n()->getLanguageCode(); ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1">
    <title><?= Html::encode($this->getDocumentTitle()); ?></title>
    <?php $this->head() ?>
</head>
<body hx-select="#wrap" hx-select-oob="#flashes:beforeend" hx-swap="outerHTML show:top" hx-target="#wrap" hx-boost="true">
<?php $this->beginBody() ?>
<?= Flashes::make(); ?>
<div id="wrap" hx-headers='{"X-CSRF-TOKEN":"<?= Yii::$app->getRequest()->getCsrfToken(); ?>"}'>
    <?= NavBar::make(); ?>
    <main class="main">
        <?= Breadcrumb::make(); ?>
        <?= $content ?>
        <?php $this->endBody() ?>
    </main>
</div>
</body>
</html>
<?php $this->endPage() ?>