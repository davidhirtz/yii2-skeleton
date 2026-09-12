<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\AccountDeleteForm;
use Hirtz\Skeleton\Models\Forms\DeleteForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Override;
use Yii;

/**
 * @property User $model
 */
class AccountDeleteActiveForm extends DeleteActiveForm
{
    public array $inputAttributes = [
        'autocomplete' => 'off',
        'type' => 'password',
    ];

    #[Override]
    protected function configure(): void
    {
        $this->message ??= Yii::t('skeleton', 'ACCOUNT_DELETE_TYPE_PASSWORD');
        parent::configure();
    }

    #[Override]
    protected function getDeleteForm(): DeleteForm
    {
        return AccountDeleteForm::create([
            'user' => $this->model,
        ]);
    }
}
