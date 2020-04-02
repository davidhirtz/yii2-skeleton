<?php

namespace davidhirtz\yii2\skeleton\models\base;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class UserLogin
 * @package davidhirtz\yii2\skeleton\models\base
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
            'DateTimeBehavior' => 'davidhirtz\yii2\datetime\DateTimeBehavior',
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
                return Yii::t('skeleton', 'Login');

            case static::TYPE_COOKIE:
                return Yii::t('skeleton', 'Cookie');

            case static::TYPE_SIGNUP:
                return Yii::t('skeleton', 'Sign up');

            case static::TYPE_CONFIRM_EMAIL:
                return Yii::t('skeleton', 'Email confirmation');

            case static::TYPE_RESET_PASSWORD:
                return Yii::t('skeleton', 'Password reset');
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

        return null;
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
            'typeName' => Yii::t('skeleton', 'Login'),
            'browser' => Yii::t('skeleton', 'Browser'),
            'ip' => Yii::t('skeleton', 'IP'),
            'user' => Yii::t('skeleton', 'User'),
            'created_at' => Yii::t('skeleton', 'Login'),
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
