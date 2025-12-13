<?php
declare(strict_types=1);

/**
 * @see ErrorAction::renderHtmlResponse()
 *
 * @var View $this
 * @var string $email
 * @var Exception $exception
 * @var string $message
 * @var string $name
 */

use Hirtz\Skeleton\Web\ErrorAction;
use Hirtz\Skeleton\Web\View;

$this->title($name);
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
