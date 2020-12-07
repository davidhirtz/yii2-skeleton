<?php

namespace davidhirtz\yii2\skeleton\models\base;

use DateTimeZone;
use davidhirtz\yii2\datetime\Date;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\models\Trail;
use yii\db\ActiveQuery;
use yii\helpers\Url;
use Yii;

/**
 * Class User
 * @package davidhirtz\yii2\skeleton\models\base
 *
 * @property int $id
 * @property int $status
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $password_salt
 * @property string $first_name
 * @property string $last_name
 * @property Date $birthdate
 * @property string $city
 * @property string $country
 * @property string $picture
 * @property string $language
 * @property string $timezone
 * @property string $email_confirmation_code
 * @property string $password_reset_code
 * @property int $is_owner
 * @property int $created_by_user_id
 * @property int $login_count
 * @property DateTime $last_login
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @method static \davidhirtz\yii2\skeleton\models\User findOne($condition)
 * @method static \davidhirtz\yii2\skeleton\models\User[] findAll($condition)
 *
 * @property \davidhirtz\yii2\skeleton\models\User $admin {@link \davidhirtz\yii2\skeleton\models\User::getAdmin()}
 * @property AuthClient[] $authClients {@link \davidhirtz\yii2\skeleton\models\User::getAuthClients()}
 * @property string $uploadPath
 */
abstract class User extends ActiveRecord
{
    use StatusAttributeTrait;

    /**
     * Constants.
     */
    public const STATUS_ENABLED = 1;

    public const GENDER_UNKNOWN = 0;
    public const GENDER_FEMALE = 1;
    public const GENDER_MALE = 2;

    public const EMAIL_CONFIRMATION_CODE_LENGTH = 30;
    public const PASSWORD_RESET_CODE_LENGTH = 30;

    /**
     * @var int
     */
    public $requireName = true;

    /**
     * @var int
     */
    public $nameMinLength = 3;

    /**
     * @var int
     */
    public $nameMaxLength = 32;

    /**
     * @var string
     */
    public $namePattern = '/^\d*[a-z][a-z0-9\.-]*[a-z0-9]$/si';

    /**
     * @var int
     */
    public $passwordMinLength = 5;

    /**
     * @var string|bool set false to disabled profile pictures
     */
    private $_uploadPath = 'uploads/users/';

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => 'davidhirtz\yii2\datetime\DateTimeBehavior',
            'TimestampBehavior' => 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior',
            'TrailBehavior' => [
                'class' => 'davidhirtz\yii2\skeleton\behaviors\TrailBehavior',
                'model' => \davidhirtz\yii2\skeleton\models\User::class,
                'attributes' => $this->getTrailAttributes(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                ['name', 'email', 'password', 'city', 'country', 'first_name', 'last_name'],
                'trim',
            ],
            [
                ['email'],
                'required',
            ],
            [
                ['status'],
                'validateStatus',
            ],
            [
                ['name'],
                $this->requireName ? 'required' : 'safe',
            ],
            [
                ['name'],
                'string',
                'min' => $this->nameMinLength,
                'max' => max($this->nameMinLength, $this->nameMaxLength),
                'skipOnError' => true,
            ],
            [
                ['name'],
                'match',
                'pattern' => $this->namePattern,
                'message' => Yii::t('skeleton', 'Username must only contain alphanumeric characters.'),
                'skipOnError' => true,
            ],
            [
                ['name'],
                'unique',
                'message' => Yii::t('skeleton', 'This username is already used by another user.'),
                'skipOnError' => true,
                'when' => function () {
                    return $this->isAttributeChanged('name');
                }
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
                'message' => Yii::t('skeleton', 'This email is already used by another user.'),
                'skipOnError' => true,
                'when' => function () {
                    return $this->isAttributeChanged('email');
                }
            ],
            [
                ['password'],
                'string',
                'min' => $this->passwordMinLength,
            ],
            [
                ['city', 'first_name', 'last_name'],
                'string',
                'max' => 50,
            ],
            [
                ['language'],
                /** {@see \davidhirtz\yii2\skeleton\models\User::validateLanguage()} */
                'validateLanguage',
            ],
            [
                ['timezone'],
                /** {@see \davidhirtz\yii2\skeleton\models\User::validateTimezone()} */
                'validateTimezone',
            ],
            [
                ['country'],
                /** {@see \davidhirtz\yii2\skeleton\models\User::validateCountry()} */
                'validateCountry',
            ]
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
        if (!in_array($this->timezone, DateTimeZone::listIdentifiers())) {
            $this->timezone = Yii::$app->getTimeZone();
        }
    }

    /**
     * Sanitizes language.
     * @see User::rules()
     */
    public function validateLanguage()
    {
        if (!in_array($this->language, Yii::$app->getI18n()->languages)) {
            $this->language = Yii::$app->language;
        }
    }

    /**
     * Sanitizes country.
     * @see User::rules()
     */
    public function validateCountry()
    {
        if ($this->country) {
            if (!isset(static::getCountries()[$this->country])) {
                $this->country = null;
            }
        }
    }

    /**
     * @return bool
     */
    public function beforeValidate(): bool
    {
        if ($this->status === null || $this->is_owner) {
            $this->status = static::STATUS_ENABLED;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritDoc
     */
    public function afterValidate()
    {
        if (!$this->name) {
            $this->name = null;
        }

        parent::afterValidate();
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->is_owner = !static::find()->exists();
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            if (isset($changedAttributes['picture'])) {
                $this->deletePicture($changedAttributes['picture']);
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Deletes picture after delete.
     */
    public function afterDelete()
    {
        if ($this->picture) {
            $this->deletePicture($this->picture);
        }

        parent::afterDelete();
    }

    /**
     * @return UserQuery
     */
    public function getAdmin(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(\davidhirtz\yii2\skeleton\models\User::class, ['id' => 'created_by_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAuthClients(): ActiveQuery
    {
        return $this->hasMany(AuthClient::class, ['user_id' => 'id']);
    }

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
     * @param string|null $password
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
            $this->addError('id', Yii::t('skeleton', 'This user is the website owner. Please transfer ownership to another user before deleting this user.'));
            return false;
        }

        return parent::delete();
    }

    /**
     * @param string|null $except
     */
    public function afterPasswordChange($except = null)
    {
        $trail = new Trail();
        $trail->model = \davidhirtz\yii2\skeleton\models\User::class;
        $trail->model_id = $this->id;
        $trail->type = Trail::TYPE_PASSWORD;
        $trail->insert();

        $this->deleteAuthKeys();
        $this->deleteActiveSessions($except);
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
     * @param string|null $except
     * @return int
     */
    public function deleteActiveSessions($except = null)
    {
        return $this->getDb()->createCommand()
            ->delete(Session::tableName(), '[[user_id]]=:userId AND [[id]]!=:id', [
                ':userId' => $this->id,
                ':id' => (string)$except
            ])
            ->execute();
    }

    /**
     * @param string $picture
     * @return bool
     */
    public function deletePicture($picture): bool
    {
        return $picture ? FileHelper::removeFile($this->getUploadPath() . $picture) : false;
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
    public function getUsername()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmailConfirmationUrl()
    {
        return $this->email_confirmation_code ? Url::to(['account/confirm', 'email' => $this->email, 'code' => $this->email_confirmation_code], true) : null;
    }

    /**
     * @return string
     */
    public function getPasswordResetUrl()
    {
        return $this->password_reset_code ? Url::to(['account/reset', 'email' => $this->email, 'code' => $this->password_reset_code], true) : null;
    }

    /**
     * @return string
     */
    public function getTimezoneOffset()
    {
        $date = new \DateTime('now');
        return 'GMT ' . $date->format('P');
    }

    /**
     * @return bool|false
     */
    public function getUploadPath()
    {
        return $this->_uploadPath ? (Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . $this->_uploadPath) : false;
    }

    /**
     * @param string|false $uploadPath
     */
    public function setUploadPath($uploadPath)
    {
        $this->_uploadPath = trim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return bool|false
     */
    public function getBaseUrl()
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $this->_uploadPath);
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'Disabled'),
                'icon' => 'exclamation-triangle',
            ],
            static::STATUS_ENABLED => [
                'name' => Yii::t('skeleton', 'Enabled'),
                'icon' => 'user',
            ],
        ];
    }

    /**
     * @return string
     */
    public function getStatusName(): string
    {
        return !$this->isOwner() ? static::getStatuses()[$this->status]['name'] : Yii::t('skeleton', 'Site Owner');
    }

    /**
     * @return string
     */
    public function getStatusIcon(): string
    {
        return !$this->isOwner() ? static::getStatuses()[$this->status]['icon'] : 'star';
    }

    /**
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'password',
            'password_salt',
            'email_confirmation_code',
            'password_reset_code',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    /**
     * @return string
     */
    public function getTrailModelName(): string
    {
        return $this->id ? $this->getUsername() : Yii::t('skeleton', 'Deleted user');
    }

    /**
     * @param $clientName
     * @return bool
     */
    public function hasAuthClient($clientName): bool
    {
        return ($authClients = $this->authClients) ? in_array($clientName, ArrayHelper::getColumn($authClients, 'name')) : false;
    }

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
    public function isUnconfirmed(): bool
    {
        return !$this->isOwner() && !empty($this->email_confirmation_code);
    }

    /**
     * @return array
     */
    public static function getCountries(): array
    {
        return require(Yii::getAlias('@skeleton/messages/') . Yii::$app->language . '/countries.php');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeleton', 'ID'),
            'status' => Yii::t('skeleton', 'Status'),
            'name' => Yii::t('skeleton', 'Username'),
            'email' => Yii::t('skeleton', 'Email'),
            'password' => Yii::t('skeleton', 'Password'),
            'first_name' => Yii::t('skeleton', 'First name'),
            'last_name' => Yii::t('skeleton', 'Last name'),
            'birthdate' => Yii::t('skeleton', 'Birthdate'),
            'city' => Yii::t('skeleton', 'City'),
            'country' => Yii::t('skeleton', 'Country'),
            'picture' => Yii::t('skeleton', 'Picture'),
            'language' => Yii::t('skeleton', 'Language'),
            'timezone' => Yii::t('skeleton', 'Timezone'),
            'email_confirmation_code' => Yii::t('skeleton', 'Email confirmation code'),
            'login_count' => Yii::t('skeleton', 'Login count'),
            'last_login' => Yii::t('skeleton', 'Last login'),
            'is_owner' => Yii::t('skeleton', 'Website owner'),
            'updated_at' => Yii::t('skeleton', 'Updated'),
            'created_at' => Yii::t('skeleton', 'Created'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return 'User';
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }
}