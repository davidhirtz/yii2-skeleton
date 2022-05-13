<?php

namespace davidhirtz\yii2\skeleton\db;

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
     * @inheritDoc
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @return string|null
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
}