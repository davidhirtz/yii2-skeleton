<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
use Override;
use Stringable;
use Yii;

/**
 * @property OwnershipForm $model
 */
class OwnershipActiveForm extends ActiveForm
{
    public bool $hasStickyButtons = false;
    public string $layout = "{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            $this->getHelpText(),
            $this->getUsernameField(),
        ];

        $this->submitButtonText ??= Yii::t('skeleton', 'Transfer');

        parent::configure();
    }

    protected function getUsernameField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('name');
    }

    protected function getHelpText(): ?Stringable
    {
        return FormRow::make()
            ->content(Yii::t('skeleton', 'Enter the username of the user you want to make owner of this site. This will remove all your admin privileges and there is no going back. Please be certain!'));
    }
}
