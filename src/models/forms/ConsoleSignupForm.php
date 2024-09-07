<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

class ConsoleSignupForm extends Model
{
    use ModelTrait;

    public ?string $email = null;
    public ?string $name = null;
    public ?string $password = null;

    public readonly User $user;

    public function __construct($config = [])
    {
        $this->user = User::create();
        parent::__construct($config);
    }

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

    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->user->status ??= User::STATUS_ENABLED;
            $this->user->email = $this->email;
            $this->user->name = $this->name;
            $this->user->generatePasswordHash($this->password);

            if (!$this->user->validate()) {
                $this->addErrors($this->user->getErrors());
            }
        }

        parent::afterValidate();
    }

    public function insert(): bool
    {
        return $this->validate() && $this->user->insert(false);
    }

    public function attributeLabels(): array
    {
        return [
            ...$this->user->attributeLabels(),
            'password' => Yii::t('skeleton', 'Password'),
        ];
    }
}
