<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

class ConsoleSignupForm extends AbstractSignupForm
{
    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['email', 'name'],
                'trim',
            ],
            [
                ['email', 'name', 'password'],
                'required',
            ],
            [
                ['password'],
                'string',
                'min' => $this->user->passwordMinLength,
            ],
        ];
    }

    #[\Override]
    protected function beforeInsert(): bool
    {
        $this->user->is_owner = !$this->user::find()->exists();
        return parent::beforeInsert();
    }
}
