<?php
/**
 * @var View|null $this
 * @var  Throwable $exception
 * @var  ErrorHandler $handler
 */

use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\NavBar;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Breadcrumbs;
use yii\base\UserException;
use yii\helpers\Html;
use yii\web\ErrorHandler;

$code = $exception instanceof \yii\web\HttpException
    ? $exception->statusCode
    : $exception->getCode();

$name = $handler->getExceptionName($exception) ?? Yii::t('yii', 'Error');
$name .= " (#$code)";

$message = $exception instanceof UserException
    ? $exception->getMessage()
    : Yii::t('yii', 'An internal server error occurred.');


AdminAsset::register($this);

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->getI18n()->getLanguageCode(); ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <title><?= $handler->htmlEncode($name); ?></title>
        <?php $this->head() ?>
    </head>
    <body>
    <?php $this->beginBody() ?>
    <div class="wrap">
        <header>
            <?php
            if (!Yii::$app->getUser()->getIsGuest()) {
                echo NavBar::widget();
            }
            ?>
        </header>
        <main>
            <div class="container">
                <?= !Yii::$app->getUser()->getIsGuest() ? Breadcrumbs::widget() : ''; ?>
                <div class="site-error">
                    <h1><?= $handler->htmlEncode($message); ?></h1>
                    <h2><?= nl2br($handler->htmlEncode($name)); ?></h2>
                    <p>
                        <?= Yii::t('skeleton', 'The above error occurred while the webserver was processing your request.'); ?>
                        <br>
                        <?= Yii::t('skeleton', 'Please contact us if you think this is a server error. Thank you.'); ?>
                    </p>
                </div>
            </div>
        </main>
    </div>
    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>