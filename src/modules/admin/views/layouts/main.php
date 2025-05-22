<?php
declare(strict_types=1);

/**
 * @var View $this
 * @var string $content
 */

use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\modules\admin\widgets\Flashes;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\Breadcrumbs;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\NavBar;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Html;

AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->getI18n()->getLanguageCode(); ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->getDocumentTitle()); ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= NavBar::widget(); ?>
<main hx-select="main" hx-swap="outerHTML show:top" hx-target="this">
    <?= Breadcrumbs::widget(); ?>
    <?= Flashes::widget(); ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</main>
</body>
</html>
<?php $this->endPage() ?>
