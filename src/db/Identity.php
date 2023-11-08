<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

class Identity extends User implements IdentityInterface
{
    public ?string $loginType = null;
    public ?string $ipAddress = null;
    public int $cookieLifetime = 2_592_000;

    public static function findIdentity($id): ?IdentityInterface
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

    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    public function getId(): mixed
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }
}
