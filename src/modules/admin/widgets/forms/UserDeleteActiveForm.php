<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use Override;
use Yii;

/**
 * @see UserController::actionDelete()
 */
class UserDeleteActiveForm extends DeleteActiveForm
{
    #[Override]
    protected function configure(): void
    {
        $this->message ??= Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!');
        $this->confirm ??= Yii::t('skeleton', 'Are you sure you want to delete this user?');

        parent::configure();
    }
}
