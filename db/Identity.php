<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\models\SessionAuthKey;
use davidhirtz\yii2\skeleton\models\User;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use Yii;

/**
 * Class Identity
 * @package davidhirtz\yii2\skeleton\db
 */
class Identity extends User implements IdentityInterface
{
    /**
     * @var int
     */
    public $loginType;

    /**
     * @var string
     */
    public $ipAddress;

    /**
     * @var int
     */
    public $cookieLifetime = 2592000;

    /**
     * @var SessionAuthKey
     */
    private $_authKey;

    /**
     * @inheritDoc
     */
    public static function findIdentity($id)
    {
        /**
         * @var Identity $identity
         */
        $identity = static::find()
            ->where(['id' => $id])
            ->selectIdentityAttributes()
            ->enabled()
            ->one();

        if ($identity && $identity->timezone) {
            Yii::$app->timeZone = $identity->timezone;
        }

        return $identity;
    }

    /**
     * @inheritDoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Validates the identity cookie with the given `authKey`. The session auth key won't be needed as a new key will be
     * generated after the validation in {@link \davidhirtz\yii2\skeleton\web\User::getIdentityAndDurationFromCookie()}.
     *
     * @param string $authKey
     * @return true
     */
    public function validateAuthKey($authKey): bool
    {
        $this->_authKey = SessionAuthKey::find()
            ->where('[[id]]=:id AND [[user_id]]=:userId AND [[expire]]>:expired')
            ->params([
                'id' => $authKey,
                'userId' => $this->id,
                'expired' => time(),
            ])
            ->one();

        return $this->_authKey !== null;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * Updates the {@link SessionAuthKey::$expire} if the key was validated by {@link Identity::validateAuthKey()} or
     * generates a new auth key otherwise.
     */
    public function getAuthKey()
    {
        if ($this->_authKey !== null) {
            SessionAuthKey::updateAll(['expire' => time() + $this->cookieLifetime], ['id' => $this->_authKey->id]);
            return $this->_authKey->id;
        }

        $columns = [
            'id' => $this->_authKey ? $this->_authKey->id : Yii::$app->getSecurity()->generateRandomString(64),
            'user_id' => $this->id,
            'expire' => time() + $this->cookieLifetime,
        ];

        Yii::$app->getDb()->createCommand()
            ->insert(SessionAuthKey::tableName(), $columns)
            ->execute();

        return $columns['id'];
    }
}