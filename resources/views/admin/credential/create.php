<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController::actionCreate()
 *
 * @var View $this
 * @var UserCredentialForm $form
 */

use Hirtz\Skeleton\Models\Forms\UserCredentialForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\UserCredentialActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('skeleton', 'CREDENTIAL_CREATE_TITLE'));

echo AccountHeader::make()
    ->model($form->user);

echo AccountSubmenu::make();

echo HintAlert::make()
    ->text(Yii::t('skeleton', 'CREDENTIAL_CREATE_HINT'));

echo FormContainer::make()
    ->form(UserCredentialActiveForm::make()
        ->model($form));
