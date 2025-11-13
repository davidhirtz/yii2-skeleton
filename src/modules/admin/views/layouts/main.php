<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var string $content
 */

use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\Flashes;
use davidhirtz\yii2\skeleton\widgets\navs\Breadcrumbs;
use davidhirtz\yii2\skeleton\widgets\navs\NavBar;
use yii\helpers\Html;

AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->getI18n()->getLanguageCode(); ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->getDocumentTitle()); ?></title>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body hx-select="main" hx-swap="outerHTML" hx-target="main">
<?php $this->beginBody() ?>
<?= NavBar::widget(); ?>
<main class="main">
    <?= Breadcrumbs::widget(); ?>
    <?= Flashes::widget(); ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</main>
</body>
</html>
<?php $this->endPage() ?>
