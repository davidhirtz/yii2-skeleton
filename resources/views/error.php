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

use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\ErrorAction;
use Hirtz\Skeleton\Web\View;

$this->title($name);
?>
    <h1><?= $message ?: $name; ?></h1>
<?php if (Application::current()->getResponse()->getIsServerError()) {
    ?>
    <p><?= Yii::t('skeleton', 'ERROR_GET_IN_TOUCH', ['email' => $email]); ?></p>
    <?php if (Application::current()->getUser()->can('admin')) {
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
