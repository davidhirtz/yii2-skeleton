<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\base\Model;

/**
 * Class LoginForm.
 * @package davidhirtz\yii2\skeleton\models\forms\base
 *
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class LoginForm extends Model
{
    use IdentityTrait;

    /**
     * @var bool
     */
    public $enableFacebookLogin = true;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var bool
     */
    public $rememberMe = true;

    /**
     * @var integer
     */
    public $cookieLifetime;

    /**
     * @var integer
     */
    public $ip;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['email', 'password'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['email', 'password'],
                'required',
            ],
            [
                ['email'],
                'email',
            ],
            [
                ['rememberMe'],
                'boolean',
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

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError('email', Yii::t('skeleton', 'Your email or password are incorrect.'));
            } elseif ($user->isDisabled() && !$user->isOwner()) {
                $this->addError('status', Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
            } elseif ($user->isUnconfirmed() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                $this->addError('status', Yii::t('skeleton', 'Your email address is not confirmed yet. You should find a confirmation email in your inbox.'));
            } else {
                $this->addErrors($user->getErrors());
            }
        }

        parent::afterValidate();
    }

    /**
     * Logs in a user using the provided email and password.
     * @return bool
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->generatePasswordHash($this->password);
            $user->loginType = UserLogin::TYPE_LOGIN;
            $user->ip = $this->ip;

            return Yii::$app->getUser()->login($user, $this->rememberMe ? ($this->cookieLifetime ?: $user->cookieLifetime) : 0);
        }

        return false;
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function isFacebookLoginEnabled()
    {
        return $this->enableFacebookLogin && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rememberMe' => Yii::t('skeleton', 'Keep me logged in'),
        ];
    }
}