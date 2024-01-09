<?php
/**
 * @var string $content
 * @var View $this
 */

use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= $this->getHtmlLangAttribute(); ?>">
    <head>
        <meta charset="<?= Yii::$app->charset; ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags(); ?>
        <title><?= Html::encode($this->getDocumentTitle()); ?></title>
        <?php $this->head(); ?>
    </head>
    <body>
    <?php $this->beginBody(); ?>
    <?= $content; ?>
    <?php $this->endBody(); ?>
    </body>
    </html>
<?php $this->endPage(); ?>