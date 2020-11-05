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
        $params = [
            'id' => $authKey,
            'userId' => $this->id,
            'expired' => time(),
        ];

        return SessionAuthKey::deleteAll('[[id]]=:id AND [[user_id]]=:userId AND [[expire]]>:expired', $params) == 1;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritDoc
     */
    public function getAuthKey()
    {
        $columns = [
            'id' => Yii::$app->getSecurity()->generateRandomString(64),
            'user_id' => $this->id,
            'expire' => time() + $this->cookieLifetime,
        ];

        Yii::$app->getDb()->createCommand()
            ->insert(SessionAuthKey::tableName(), $columns)
            ->execute();

        return $columns['id'];
    }
}