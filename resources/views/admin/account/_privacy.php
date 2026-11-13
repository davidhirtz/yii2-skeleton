<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Module::$privacyUrl
 *
 * @var Hirtz\Skeleton\Web\View $this
 */

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Modules\Admin\Module;

$url = Module::current()->privacyUrl;

echo P::make()
    ->class('privacy small text-muted text-center')
    ->text(Yii::t('skeleton', 'ACCOUNT_PRIVACY_TEXT'))
    ->addContent($url !== null
        ? ' ' . Yii::t('skeleton', 'ACCOUNT_PRIVACY_POLICY_TEXT', [
            'link' => A::make()
                ->text(Yii::t('skeleton', 'ACCOUNT_PRIVACY_LINK'))
                ->href($url)
                ->target('_blank'),
        ])
        : null);
