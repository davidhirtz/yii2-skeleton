<?php
/**
 * @see ErrorAction::renderHtmlResponse()
 *
 * @var View $this
 * @var int|string|null $code
 * @var string $email
 * @var Exception $exception
 * @var string $message
 * @var string $name
 */

use davidhirtz\yii2\skeleton\web\ErrorAction;
use davidhirtz\yii2\skeleton\web\View;

$this->setTitle($name);
?>
    <h1><?= $message ?: $name; ?></h1>
<?php if (Yii::$app->getResponse()->getIsServerError()) {
    ?>
    <p><?= Yii::t('skeleton', 'Please get in touch with {email}', ['email' => $email]); ?></p>
    <?php if (Yii::$app->getUser()->can('admin')) {
        ?>
        <p></p>
        <h2><?= $exception->getMessage() ?: 'Unknown Error'; ?></h2>
        <p>Exception: <?= $exception::class; ?></p>
        <p>Code: <?= $exception->getCode(); ?></p>
        <p></p>
        <p><?= $exception->getTraceAsString() ?></p>
        <?php
    } ?>
    <?php
}
