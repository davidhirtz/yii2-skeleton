<?php
/**
 * Upload form.
 * @see \davidhirtz\yii2\skeleton\widgets\grid\ItemGrid
 *
 * @var \yii\web\View $this
 * @var $upload string
 */
?>
<hr>
<div class="row">
    <div class="offset-md-4 col-md-8 col-lg-6">
        <p><?= Yii::t('app', 'Upload multiple files simultaneously. After the upload was completed, drag and drop files to order them.'); ?></p>
        <span class="btn btn-secondary btn-submit btn-upload">
			<span><?= Yii::t('app', 'Upload'); ?></span>
			<?= $upload; ?>
		</span>
    </div>
</div>