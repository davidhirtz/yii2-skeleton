<?php
declare(strict_types=1);

/**
 * @var View $this
 * @var MessageInterface $message
 * @var User $user
 * @var string $url
 */

use Hirtz\Skeleton\Models\User;
use yii\mail\MessageInterface;
use yii\web\View;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_CREATE_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => $user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_CREATE_TEXT'); ?><br>
</p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
<div class="btn-wrap">
    <a href="<?= $url; ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_EMAIL_BUTTON'); ?></a>
</div>
