<?php
/**
 * @see ErrorController::actionIndex()
 *
 * @var View $this
 * @var Exception $exception
 * @var Response $response
 * @var string $email
 */

use davidhirtz\yii2\skeleton\controllers\ErrorController;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Html;
use yii\web\Response;

$this->setTitle($response->getIsNotFound() ? Yii::t('skeleton', 'Page not found') : Yii::t('skeleton', 'Error'));
?>
<div class="wrap">
    <div class="section box prose">
        <?php if ($response->getIsNotFound()) {
            ?>
            <h1><?= Yii::t('skeleton', 'The requested page was not found'); ?></h1>
            <?php
        } elseif ($response->getIsForbidden()) {
            ?>
            <h1><?= Yii::t('skeleton', 'Permission denied'); ?></h1>
            <?php
        } else {
            ?>
            <h1><?= Yii::t('skeleton', 'Internal Server Error'); ?></h1>
            <p>
                <?= Yii::t('skeleton', 'Please get in touch with {email}', [
                    'email' => Html::a(Html::encode($email), "mailto:$email"),
                ]); ?>
            </p>
            <?php if (Yii::$app->getUser()->can('admin')) {
                ?>
                <p></p>
                <h2><?= $exception->getMessage() ?: 'Unknown Error'; ?></h2>
                <p>Code: <?= $exception->getCode(); ?></p>
                <p></p>
                <p><?= $exception->getTraceAsString() ?></p>
                <?php
            } ?>
            <?php
        } ?>
    </div>
</div>