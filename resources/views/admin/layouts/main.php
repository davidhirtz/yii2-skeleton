<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var string $content
 */

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Modules\Admin\TimezoneModal;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsideToggleButton;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBar;
use Hirtz\Skeleton\Web\Application;
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
    <body hx-select:inherited="#wrap" hx-swap:inherited="outerHTML show:top" hx-target:inherited="#wrap" hx-boost:inherited="true">
    <?php $this->beginBody() ?>
    <div class="wrap">
        <?= NavBar::make(); ?>
        <?= Flashes::make(); ?>
        <div class="layout" id="wrap" data-depth="<?= $this->depth; ?>" hx-select-oob:inherited="#flashes:beforeend" hx-headers:inherited='{"X-CSRF-TOKEN":"<?= Application::current()->getRequest()->getCsrfToken(); ?>"}'>
            <?= AsideMenu::make(); ?>
            <?= TimezoneModal::make(); ?>
            <main class="main">
                <?= Breadcrumbs::make(); ?>
                <?= $content ?>
            </main>
            <?php $this->endBody() ?>
        </div>
        <button class="aside-close" data-aside></button>
    </div>
    </body>
    </html>
<?php $this->endPage() ?>