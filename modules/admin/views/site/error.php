<?php
/**
 * Admin error page.
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">
    <h1 class="page-header"><?= $name; ?></h1>
    <?php
    if ($message) {
        ?>
        <div class="alert alert-danger">
            <?= nl2br(Html::encode($message)) ?>
        </div>
        <?php
    } else {
        ?>
        <div class="alert alert-warning">
            <p>
                <?= Yii::t('skeleton', 'The above error occurred while the Web server was processing your request.'); ?><br>
                <?= Yii::t('skeleton', 'Please contact us if you think this is a server error. Thank you.'); ?>
            </p>
        </div>
        <?php
    }
    ?>
</div>
