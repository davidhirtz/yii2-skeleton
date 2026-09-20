<?php
declare(strict_types=1);

/**
 * @var View|null $this
 * @var Throwable $exception
 * @var ErrorHandler $handler
 */

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\View;
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


AdminAssetBundle::register($this);

$colorScheme = Module::current()->getColorScheme();

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->getI18n()->getLanguageCode(); ?>"<?= $colorScheme ? ' data-theme="' . $colorScheme . '"' : ''; ?>>
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
        <main>
            <div class="container">
                <ul class="breadcrumb hidden">
                    <li class="breadcrumb-item"><a href="/"><?= Yii::$app->name; ?></a></li>
                </ul>
                <h1><?= $handler->htmlEncode($message); ?></h1>
                <h2><?= nl2br($handler->htmlEncode($name)); ?></h2>
                <p>
                    <?= Yii::t('skeleton', 'ERROR_ABOVE_OCCURRED'); ?>
                    <br>
                    <?= Yii::t('skeleton', 'ERROR_CONTACT_US'); ?>
                </p>
            </div>
        </main>
    </div>
    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>
