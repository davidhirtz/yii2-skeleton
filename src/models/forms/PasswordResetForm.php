<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\base\Model;

/**
 * Class PasswordResetForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 *
 * @property Identity $user
 * @see PasswordResetForm::getUser()
 */
class PasswordResetForm extends Model
{
    use IdentityTrait;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $newPassword;

    /**
     * @var string
     */
    public $repeatPassword;

    
    public function rules()
    {
        return [
            [
                ['email', 'code', 'newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['code'],
                'string',
                'length' => 32,
            ],
            [
                ['newPassword', 'repeatPassword'],
                'required',
            ],
            [
                ['newPassword'],
                'string',
                'min' => User::instance()->passwordMinLength,
            ],
            [
                ['repeatPassword'],
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Yii::t('skeleton', 'The password must match the new password.'),
            ],
        ];
    }

    /**
     * Validates user credentials and password reset code.
     */
    public function afterValidate(): void
    {
        $this->validatePasswordResetCode();
        $this->validateUserStatus();

        parent::afterValidate();
    }

    /**
     * Validates password reset code if user was found by email.
     */
    public function validatePasswordResetCode(): bool
    {
        if (!$this->hasErrors() && (!($user = $this->getUser()) || $user->password_reset_token != $this->code)) {
            $this->addError('id', Yii::t('skeleton', 'The password recovery url is invalid.'));
        }

        return !$this->hasErrors();
    }

    /**
     * Hashes new password and logs in user if possible.
     * This method also deletes all cookie auth keys for this user, so auto login cookies are not working anymore.
     */
    public function reset()
    {
        if ($this->validate()) {
            $user = $this->getUser();

            $user->generateAuthKey();
            $user->generatePasswordHash($this->newPassword);
            $user->password_reset_token = null;

            if (Yii::$app->getUser()->getIsGuest()) {
                if (!$user->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                    $user->loginType = UserLogin::TYPE_RESET_PASSWORD;

                    $user->afterPasswordChange();

                    // Login also takes care of updating the user record.
                    return Yii::$app->getUser()->login($user);
                }
            }

            return $user->update(false);
        }

        return false;
    }

    
    public function attributeLabels()
    {
        $user = $this->getUser();

        return [
            'newPassword' => $user && $user->login_count ? Yii::t('skeleton', 'New password') : Yii::t('skeleton', 'Password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
        ];
    }
}
