<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use yii\base\Model;
use Yii;

/**
 * Class AccountConfirmForm
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class AccountConfirmForm extends Model
{
    use IdentityTrait;

    /**
     * @var string
     */
    public $code;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['email'],
                'filter',
                'filter' => 'trim',
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

    /**
     * Validates user credentials.
     */
    public function afterValidate()
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || $user->verification_token != $this->code) {
                $this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
                    'attribute' => $this->getAttributeLabel('code'),
                ]));
            }
        }

        parent::afterValidate();
    }

    /**
     * Logs in a user using the provided email and password.
     * @return bool
     */
    public function confirm()
    {
        if ($this->validate()) {
            $this->getUser()->updateAttributes(['verification_token' => null]);
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('skeleton', 'Username'),
            'code' => Yii::t('skeleton', 'Email verification code'),
        ];
    }
}