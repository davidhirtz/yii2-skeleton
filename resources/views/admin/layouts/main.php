<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var string $content
 */

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Modules\Admin\TimezoneModal;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBar;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Flashes;
use Hirtz\Skeleton\Widgets\Navs\Breadcrumbs;
use yii\helpers\Html;

AdminAssetBundle::register($this);
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
    <body hx-ext="head-support" hx-select="#wrap" hx-select-oob="#flashes:beforeend" hx-swap="outerHTML show:top" hx-target="#wrap" hx-boost="true">
    <?php $this->beginBody() ?>
    <?= NavBar::make(); ?>
    <?= Flashes::make(); ?>
    <div class="layout" id="wrap" hx-headers='{"X-CSRF-TOKEN":"<?= Yii::$app->getRequest()->getCsrfToken(); ?>"}'>
        <?= AsideMenu::make(); ?>
        <?= TimezoneModal::make(); ?>
        <main class="main">
            <?= Breadcrumbs::make(); ?>
            <?= $content ?>
            <?php $this->endBody() ?>
        </main>
    </div>
    </body>
    </html>
<?php $this->endPage() ?>