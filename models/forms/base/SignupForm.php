<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;

/**
 * Class SignupForm.
 * @package davidhirtz\yii2\skeleton\models\forms\base
 * @see \davidhirtz\yii2\skeleton\models\forms\SignupForm
 */
class SignupForm extends Identity
{
    use \davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;

    /**
     * @var bool
     */
    public $enableFacebookSignup = true;

    /**
     * @var string honeypot text field to mess with bots, the text field will have a random value
     * which will be removed by javascript on form submit.
     */
    public $honeypot;

    /**
     * @var boolean whether user has accepted the terms of service.
     */
    public $terms;

    /**
     * @var string token text field is set by ajax and checked against cookie.
     */
    public $token;

    /**
     * @var integer the web user ip.
     */
    public $ip;

    /**
     * @var int
     */
    public $spamProtectionInSeconds = 60;

    /**
     * Cookie name.
     */
    const SESSION_TOKEN_NAME = 'signup_token';
    const SESSION_TIMESTAMP_NAME = 'signup_timestamp';
    const SESSION_MIN_TIME = 2;
    const SESSION_MAX_TIME = 1800;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['password'],
                'required',
            ],
            [
                ['terms'],
                'compare',
                'compareValue' => 1,
                'message' => Yii::t('skeleton', 'Please accept the terms of service and privacy policy.'),
                'skipOnEmpty' => false,
            ],
            [
                ['token'],
                /** {@see \davidhirtz\yii2\skeleton\models\forms\SignupForm::validateToken()} */
                'validateToken',
            ],
            [
                ['honeypot'],
                'compare',
                'compareValue' => '',
                'message' => Yii::t('skeleton', 'Sign up could not be completed, please try again.'),
            ],
        ]);
    }

    /**
     * Validates token.
     */
    public function validateToken()
    {
        if (($token = static::getSessionToken()) !== null) {
            if ($this->token !== $token) {
                $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
            }

            if (!$this->hasErrors('token')) {
                $timestamp = time() - Yii::$app->getSession()->get(static::SESSION_TIMESTAMP_NAME, 0);
                if ($timestamp < static::SESSION_MIN_TIME && $timestamp > static::SESSION_MAX_TIME) {
                    $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
                }
            }
        }
    }

    /**
     * Checks the IP address against the new signups.
     */
    public function validateIp()
    {
        if ($this->ip && $this->spamProtectionInSeconds > 0) {
            $signup = UserLogin::find()
                ->where(['type' => UserLogin::TYPE_SIGNUP, 'ip' => StringHelper::ip2Long($this->ip)])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            if ($signup && $signup->created_at->getTimestamp() > time() - $this->spamProtectionInSeconds) {
                $this->addError('id', Yii::t('skeleton', 'You have just created a new user account. Please wait a few minutes!'));
            }
        }
    }

    /**
     * @return bool
     */
    public function beforeValidate(): bool
    {
        if ($this->status === null) {
            $this->status = static::STATUS_ENABLED;
        }

        if(!$this->ip === null) {
            $this->ip = Yii::$app->getRequest()->getUserIP();
        }

        // There were some cases in which the value set by the ajax call
        // contained a leading space....
        $this->token = trim($this->token);

        return parent::beforeValidate();
    }

    /**
     * Validates signup creation time and user credentials.
     */
    public function afterValidate()
    {
        if (!$this->hasErrors()) {
            $this->validateIp();
        }

        parent::afterValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->generatePasswordHash();
            $this->generateEmailConfirmationCode();
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            if (static::getSessionToken() !== null) {
                Yii::$app->getSession()->set(static::SESSION_TOKEN_NAME, '');
            }

            $this->createUserLogin();
            $this->sendSignupEmail();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Creates user login record.
     */
    private function createUserLogin()
    {
        if (Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->loginType = UserLogin::TYPE_SIGNUP;
            Yii::$app->getUser()->login($this);
        }
    }

    /**
     * Generates a random token that is saved in the user session. Override this method to return
     * null to disabled token check.
     *
     * @return string|null
     */
    public static function getSessionToken()
    {
        $time = time();
        $session = Yii::$app->getSession();

        if ($session->get(static::SESSION_TIMESTAMP_NAME, 0) < $time - 300 || !$session->get(static::SESSION_TOKEN_NAME)) {
            $session->set(static::SESSION_TOKEN_NAME, Yii::$app->getSecurity()->generateRandomString(20));
            $session->set(static::SESSION_TIMESTAMP_NAME, $time);
        }

        return $session->get(static::SESSION_TOKEN_NAME, false);
    }

    /**
     * @return bool
     */
    public function isFacebookSignupEnabled(): bool
    {
        return $this->enableFacebookSignup && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'terms' => Yii::t('skeleton', 'I accept the terms of service and privacy policy'),
        ]);
    }
}