<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\traits\UserActiveFormTrait;
use Stringable;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    protected function renderContent(): string|Stringable
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->nameField(),
                InputField::make()
                    ->model($this->model)
                    ->property('email')
                    ->type('email'),
                InputField::make()
                    ->property('newPassword')
                    ->type('password'),
                InputField::make()
                    ->property('repeatPassword')
                    ->type('password'),
            ],
            [
                SelectField::make()
                    ->model($this->model->user)
                    ->property('language'),
                // Todo Timezone widget
                SelectField::make()
                    ->model($this->model->user)
                    ->property('timezone'),
            ],
            [
                'first_name',
                'last_name',
                'city',
                'country',
            ],
            [
                'sendEmail',
            ],
        ];

        return parent::renderContent();
    }

    public function getStatusField(): string|Stringable
    {
        return SelectField::make()
            ->model($this->model->user)
            ->property('status');
    }

    public function nameField(): string|Stringable
    {
        return InputField::make()
            ->model($this->model->user)
            ->property('name');
    }

    public function sendEmailField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'sendEmail')->checkbox($options);
    }

    protected function isNewRecord(): bool
    {
        return $this->model->user->getIsNewRecord();
    }
}
