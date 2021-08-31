<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use Yii;
use yii\base\Model;

/**
 * Class GoogleAuthenticatorForm
 * @package davidhirtz\yii2\skeleton\models\forms\base
 *
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class GoogleAuthenticatorForm extends Model
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $code;

    /**
     * @var int secret length
     */
    public $length = 16;

    /**
     * @var string
     */
    private $_secret;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['code'],
                'required',
            ],
            [
                ['code'],
                'string',
                'length' => 6,
            ],
            [
                ['code'],
                GoogleAuthenticatorValidator::class,
                'secret' => $this->getSecret(),
            ],
        ];
    }

    /**
     * Logs in a user using the provided email and password.
     * @return bool
     */
    public function save()
    {
        if ($this->validate()) {
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        if ($this->_secret === null) {
            if (!($this->_secret = Yii::$app->getSession()->get('google_2fa_secret'))) {
                $this->generateSecret();
            }
        }

        return $this->_secret;
    }

    /**
     * Generates a new secret key and saves it to session.
     */
    protected function generateSecret(): void
    {
        $this->_secret = Yii::$app->getSecurity()->generateRandomString($this->length);
        Yii::$app->getSession()->set('google_2fa_secret', $this->_secret);

        Yii::debug('New Google Authenticator secret generated');
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'GoogleAuthenticator';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('skeleton', 'Code'),
        ];
    }
}