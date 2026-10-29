<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\UserCredentialForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Override;
use Stringable;
use Yii;

/**
 * @see CredentialController::actionCreate()
 *
 * @property UserCredentialForm $model
 */
class UserCredentialActiveForm extends ActiveForm
{
    use CustomAttributeFieldsTrait;

    #[Override]
    protected function configure(): void
    {
        $this->submitButtonText ??= Yii::t('skeleton', 'CREDENTIAL_SAVE_BUTTON');
        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            [
                $this->getProviderField(),
                ...$this->getCustomAttributeFields(),
            ],
        ];
    }

    protected function getProviderField(): string|Stringable
    {
        $items = $this->model->getProviderItems();

        $field = SelectField::make()
            ->property('provider')
            ->items($items);

        return count($items) > 1 ? $field->reloadsForm() : $field;
    }
}
