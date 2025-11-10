<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use Yii;
use yii\base\Model;

class AccountConfirmForm extends Model
{
    use IdentityTrait;

    public string $code;
    
    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['email'],
                'trim',
            ],
            [
                ['email', 'code'],
                'required',
            ],
            [
                ['code'],
                'string',
                'length' => 32,
                'notEqual' => Yii::t('yii', '{attribute} is invalid.'),
                'skipOnError' => true,
            ],
        ];
    }

    #[\Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || $user->verification_token !== $this->code) {
                $this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
                    'attribute' => $this->getAttributeLabel('code'),
                ]));
            }
        }

        parent::afterValidate();
    }

    /**
     * Logs in a user using the provided email and password.
     */
    public function confirm(): bool
    {
        if ($this->validate()) {
            $this->getUser()->updateAttributes(['verification_token' => null]);
            return true;
        }

        return false;
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'Username'),
            'code' => Yii::t('skeleton', 'Email verification code'),
        ];
    }
}
