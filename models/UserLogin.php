<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Yii;
use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class Login.
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $id
 * @property integer $user_id
 * @property string $type
 * @property string $browser
 * @property integer $ip
 * @property DateTime $created_at
 *
 * @property User $user
 * @property-read string $typeName
 * @property-read string $displayIp
 */
class UserLogin extends ActiveRecord
{
    /**
     * Type codes.
     */
    const TYPE_COOKIE = 'auto';
    const TYPE_LOGIN = 'login';
    const TYPE_SIGNUP = 'signup';
    const TYPE_CONFIRM_EMAIL = 'email';
    const TYPE_RESET_PASSWORD = 'password';

    /***********************************************************************
     * Behaviors.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'DateTimeBehavior' => [
                'class' => DateTimeBehavior::class,
            ],
        ];
    }

    /***********************************************************************
     * Relations.
     ***********************************************************************/

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /***********************************************************************
     * Getters / Setters.
     ***********************************************************************/

    /**
     * @return string
     */
    public function getTypeName()
    {
        switch ($this->type) {
            case static::TYPE_LOGIN:
                return Yii::t('app', 'Login');

            case static::TYPE_COOKIE:
                return Yii::t('app', 'Cookie');

            case static::TYPE_SIGNUP:
                return Yii::t('app', 'Sign up');

            case static::TYPE_CONFIRM_EMAIL:
                return Yii::t('app', 'Email confirmation');

            case static::TYPE_RESET_PASSWORD:
                return Yii::t('app', 'Password reset');
        }

        return ucfirst($this->type);
    }

    /**
     * @return string
     */
    public function getTypeIcon()
    {
        switch ($this->type) {
            case static::TYPE_LOGIN:
                return 'sign-in-alt';

            case static::TYPE_COOKIE:
                return 'heart';

            case static::TYPE_SIGNUP:
                return 'user-plus';

            case static::TYPE_CONFIRM_EMAIL:
                return 'envelope';

            case static::TYPE_RESET_PASSWORD:
                return 'unlock';
        }
    }

    /**
     * @return string
     */
    public function getDisplayIp()
    {
        return $this->ip ? long2ip((int)$this->ip) : null;
    }

    /***********************************************************************
     * Active Record.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'typeName' => Yii::t('app', 'Login'),
            'browser' => Yii::t('app', 'Browser'),
            'ip' => Yii::t('app', 'IP'),
            'user' => Yii::t('app', 'User'),
            'created_at' => Yii::t('app', 'Login'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_login}}';
    }
}
