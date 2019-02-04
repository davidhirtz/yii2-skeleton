<?php

namespace davidhirtz\yii2\skeleton\models\base;

use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\datetime\DateTime;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\Url;
use Yii;

/**
 * Class User.
 * @package davidhirtz\yii2\skeleton\models\base
 *
 * @property integer $id
 * @property integer $status
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $password_salt
 * @property string $first_name
 * @property string $last_name
 * @property string $language
 * @property string $timezone
 * @property string $email_confirmation_code
 * @property string $password_reset_code
 * @property integer $is_owner
 * @property integer $created_by_user_id
 * @property integer $login_count
 * @property DateTime $last_login
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @method static \davidhirtz\yii2\skeleton\models\User findOne($condition)
 * @method static \davidhirtz\yii2\skeleton\models\User[] findAll($condition)
 *
 * @property \davidhirtz\yii2\skeleton\models\User $admin
 * @see \davidhirtz\yii2\skeleton\models\User::getAdmin()
 *
 * @property AuthClient[] $authClients
 * @see \davidhirtz\yii2\skeleton\models\User::getAuthClients()
 *
 */
abstract class User extends ActiveRecord
{
    /**
     * @var int
     */
    public $nameMinLength = 3;

    /**
     * @var int
     */
    public $nameMaxLength = 32;

    /**
     * @var int
     */
    public $passwordMinLength = 5;

    /**
     * Constants.
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    const GENDER_UNKNOWN = 0;
    const GENDER_FEMALE = 1;
    const GENDER_MALE = 2;

    const NAME_VALIDATION_REGEXP = '/^\d*[a-z][a-z0-9\.-]*[a-z0-9]$/si';
    const NAME_MAX_LENGTH = 32;

    const EMAIL_CONFIRMATION_CODE_LENGTH = 30;
    const PASSWORD_RESET_CODE_LENGTH = 30;

    const BASE_UPLOAD_PATH = 'uploads/users/{id}/';
    const PICTURE_UPLOAD_PATH = 'profile/';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => [
                'class' => DateTimeBehavior::class,
            ],
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'value' => function () {
                    return new DateTime;
                },
            ],
        ];
    }

    /***********************************************************************
     * Validation.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                ['name', 'email', 'password', 'city', 'country', 'first_name', 'last_name'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['status', 'name', 'email', 'language'],
                'required',
            ],
            [
                ['status'],
                'in',
                'range' => array_keys(static::getStatuses()),
            ],
            [
                ['name'],
                'string',
                'min' => $this->nameMinLength,
                'max' => max($this->nameMinLength, min($this->nameMaxLength, static::NAME_MAX_LENGTH)),
                'skipOnError' => true,
            ],
            [
                ['name'],
                'match',
                'pattern' => static::NAME_VALIDATION_REGEXP,
                'message' => Yii::t('app', 'Username must only contain alphanumeric characters.'),
                'skipOnError' => true,
            ],
            [
                ['name'],
                'unique',
                'message' => Yii::t('app', 'This username is already used by another user.'),
                'skipOnError' => true,
            ],
            [
                ['email'],
                'string',
                'max' => 100,
            ],
            [
                ['email'],
                'email',
                'skipOnError' => true,
            ],
            [
                ['email'],
                'unique',
                'message' => Yii::t('app', 'This email is already used by another user.'),
                'skipOnError' => true,
            ],
            [
                ['password'],
                'string',
                'min' => $this->passwordMinLength,
            ],
            [
                ['city', 'country', 'first_name', 'last_name'],
                'string',
                'max' => 50,
            ],
            [
                ['language'],
                'in',
                'range' => Yii::$app->getI18n()->languages,
            ],
            [
                ['timezone'],
                'validateTimezone',
            ],
        ];
    }

    /**
     * Checks password against stored password hash and salt.
     * @param string $password
     * @return bool
     */
    public function validatePassword(string $password): bool
    {
        return $this->password ? Yii::$app->security->validatePassword($password . $this->password_salt, $this->password) : false;
    }

    /**
     * Sanitizes timezone.
     * @see User::rules()
     */
    public function validateTimezone()
    {
        if (!in_array($this->timezone, \DateTimeZone::listIdentifiers())) {
            $this->timezone = null;
        }
    }

    /***********************************************************************
     * Relations.
     ***********************************************************************/

    /**
     * @return UserQuery
     */
    public function getAdmin(): UserQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAuthClients(): ActiveQuery
    {
        return $this->hasMany(AuthClient::class, ['user_id' => 'id']);
    }

    /***********************************************************************
     * Events.
     ***********************************************************************/

    /**
     * @return bool
     */
    public function beforeValidate(): bool
    {
        if (!$this->language) {
            $this->language = Yii::$app->language;
        }

        if ($this->is_owner) {
            $this->status = static::STATUS_ENABLED;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->status = static::STATUS_ENABLED;
            $this->is_owner = !static::find()->count() ? true : false;
        }

        return parent::beforeSave($insert);
    }

    /***********************************************************************
     * Methods.
     ***********************************************************************/

    /**
     * @return UserQuery
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    /**
     * @param string $email
     * @return UserQuery
     */
    public static function findByEmail(string $email): UserQuery
    {
        return static::find()->whereLower([static::tableName() . '.[[email]]' => $email]);
    }

    /**
     * @param string $name
     * @return UserQuery
     */
    public static function findByName(string $name): UserQuery
    {
        return static::find()->whereLower([static::tableName() . '.[[name]]' => $name]);
    }

    /**
     * Generates password hash.
     * @param string $password
     * @throws \yii\base\Exception
     */
    public function generatePasswordHash(string $password = null)
    {
        $this->password_salt = Yii::$app->getSecurity()->generateRandomString(10);
        $this->password = Yii::$app->getSecurity()->generatePasswordHash(($password ?: $this->password) . $this->password_salt);
    }

    /**
     * Generates email confirmation code.
     */
    public function generateEmailConfirmationCode()
    {
        $this->email_confirmation_code = Yii::$app->getSecurity()->generateRandomString(static::EMAIL_CONFIRMATION_CODE_LENGTH);
    }

    /**
     * Generates password code.
     */
    public function generatePasswordResetCode()
    {
        $this->password_reset_code = Yii::$app->getSecurity()->generateRandomString(static::PASSWORD_RESET_CODE_LENGTH);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        if ($this->isOwner()) {
            $this->addError('id', Yii::t('app', 'This user is the website owner. Please transfer ownership to another user before deleting this user.'));
            return false;
        }

        return parent::delete();
    }

    /**
     * Deletes all user related auth keys, rendering all auto login cookies invalid.
     * @return int
     */
    public function deleteAuthKeys()
    {
        return $this->getDb()->createCommand()
            ->delete('{{%session_auth_key}}', '[[user_id]]=:userId', [':userId' => $this->id])
            ->execute();
    }

    /**
     * Deletes user sessions.
     * @param string $except
     * @return int
     */
    public function deleteActiveSessions(string $except = null)
    {
        return $this->getDb()->createCommand()
            ->delete('{{%session}}', '[[user_id]]=:userId AND [[id]]!=:id', [
                ':userId' => $this->id,
                ':id' => (string)$except
            ])
            ->execute();
    }

    /***********************************************************************
     * Getters / setters.
     ***********************************************************************/

    /**
     * @return bool
     */
    public function isOwner(): bool
    {
        return (bool)$this->is_owner;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->status == static::STATUS_DISABLED;
    }

    /**
     * @return bool
     */
    public function isUnconfirmed(): bool
    {
        return !$this->isOwner() && !empty($this->email_confirmation_code);
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return $this->first_name && $this->last_name ? ($this->first_name[0] . $this->last_name[0]) : substr($this->name, 0, 2);
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmailConfirmationUrl(): string
    {
        return $this->email_confirmation_code ? Url::to([
            'account/confirm',
            'email' => $this->email,
            'code' => $this->email_confirmation_code
        ], true) : null;
    }

    /**
     * @return string
     */
    public function getPasswordResetUrl(): string
    {
        return $this->password_reset_code ? Url::to([
            'account/reset',
            'email' => $this->email,
            'code' => $this->password_reset_code
        ], true) : null;
    }

    /**
     * @throws \Exception
     * @return string
     */
    public function getTimezoneOffset()
    {
        $date = new \DateTime('now');
        return 'GMT ' . $date->format('P');
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            static::STATUS_DISABLED => [
                'name' => Yii::t('app', 'Disabled'),
                'icon' => 'exclamation-triangle',
            ],
            static::STATUS_ENABLED => [
                'name' => Yii::t('app', 'Enabled'),
                'icon' => 'user',
            ],
        ];
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        return !$this->isOwner() ? static::getStatuses()[$this->status]['name'] : Yii::t('app', 'Site Owner');
    }

    /**
     * @return string
     */
    public function getStatusIcon()
    {
        return !$this->isOwner() ? static::getStatuses()[$this->status]['icon'] : 'star';
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
            'id' => Yii::t('app', 'ID'),
            'status' => Yii::t('app', 'Status'),
            'name' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'first_name' => Yii::t('app', 'First name'),
            'last_name' => Yii::t('app', 'Last name'),
            'birthdate' => Yii::t('app', 'Birthdate'),
            'city' => Yii::t('app', 'City'),
            'country' => Yii::t('app', 'Country'),
            'picture' => Yii::t('app', 'Picture'),
            'language' => Yii::t('app', 'Language'),
            'timezone' => Yii::t('app', 'Timezone'),
            'email_confirmation_code' => Yii::t('app', 'Email confirmation code'),
            'login_count' => Yii::t('app', 'Login count'),
            'last_login' => Yii::t('app', 'Last login'),
            'is_owner' => Yii::t('app', 'Website owner'),
            'updated_at' => Yii::t('app', 'Updated'),
            'created_at' => Yii::t('app', 'Created'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }
}