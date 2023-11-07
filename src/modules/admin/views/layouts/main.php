<?php
/**
 * @var View $this
 * @var string $content
 */

use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Flashes;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\NavBar;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Breadcrumbs;
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
    <div class="wrap">
        <header>
            <?= !Yii::$app->getUser()->getIsGuest() ? NavBar::widget() : ''; ?>
        </header>
        <main>
            <div class="container">
                <?= !Yii::$app->getUser()->getIsGuest() ? Breadcrumbs::widget() : ''; ?>
                <?= Flashes::widget(); ?>
                <?= $content ?>
            </div>
        </main>
    </div>
    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>