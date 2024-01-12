<?php

namespace davidhirtz\yii2\skeleton\models;

use DateTimeZone;
use davidhirtz\yii2\datetime\Date;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\helpers\Image;
use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\traits\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use davidhirtz\yii2\skeleton\validators\UniqueValidator;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property int $status
 * @property string|null $name
 * @property string $email
 * @property string|null $password_hash
 * @property string|null $password_salt
 * @property string|null $first_name
 * @property string|null $last_name
 * @property Date|null $birthdate
 * @property string|null $city
 * @property string|null $country
 * @property string|null $picture
 * @property string $language
 * @property string|null $timezone
 * @property string|null $auth_key
 * @property string|null $verification_token
 * @property string|null $password_reset_token
 * @property string|null $google_2fa_secret
 * @property int $is_owner
 * @property int $created_by_user_id
 * @property int $login_count
 * @property DateTime|null $last_login
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property string $uploadPath {@see static::setUploadPath()}
 *
 * @property-read User $admin {@see static::getAdmin()}
 * @property-read AuthClient[] $authClients {@see static::getAuthClients()}
 *
 * @mixin TrailBehavior
 */
class User extends ActiveRecord implements StatusAttributeInterface
{
    use StatusAttributeTrait;

    public const AUTH_USER_CREATE = 'userCreate';
    public const AUTH_USER_DELETE = 'userDelete';
    public const AUTH_USER_UPDATE = 'userUpdate';
    public const AUTH_USER_ASSIGN = 'authUpdate';
    public const AUTH_ROLE_ADMIN = 'admin';

    /**
     * @var bool whether uploads should be automatically rotated based on their EXIF data
     */
    public bool $autorotatePicture = true;

    /**
     * @var int the minimum length for the username
     */
    public int $nameMinLength = 3;

    /**
     * @var int the maximum length for the username
     */
    public int $nameMaxLength = 32;

    /**
     * @var string the pattern for the username
     */
    public string $namePattern = '/^\d*[a-z][a-z0-9\.-]*[a-z0-9]$/si';

    /**
     * @var int the minimum length for the password
     */
    public int $passwordMinLength = 5;

    /**
     * @var bool whether the name is required
     */
    public bool $requireName = true;

    /**
     * @var UploadedFile|StreamUploadedFile|string|null the profile picture upload
     */
    public UploadedFile|StreamUploadedFile|string|null $upload = null;

    /**
     * @var array contains the allowed upload extensions for the profile picture
     */
    public array $uploadExtensions = ['gif', 'jpg', 'jpeg', 'png'];

    /**
     * @var bool whether mimetype should check the upload extension
     */
    public bool $uploadCheckExtensionByMimeType = true;

    /**
     * @var string|false set false to disabled profile pictures
     */
    private string|false $_uploadPath = 'uploads/users/';

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
            'TrailBehavior' => [
                'class' => TrailBehavior::class,
                'modelClass' => User::class,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [
                ['name', 'email', 'city', 'country', 'first_name', 'last_name'],
                'trim',
            ],
            [
                ['email'],
                'required',
            ],
            [
                /**
                 * @see static::getStatuses()
                 * @see static::getLanguages()
                 * @see static::getTimezones()
                 */
                ['status', 'language', 'timezone'],
                DynamicRangeValidator::class,
            ],
            [
                /**
                 * @see static::getCountries()
                 */
                ['country'],
                DynamicRangeValidator::class,
                'skipOnEmpty' => true,
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
                UniqueValidator::class,
                'message' => Yii::t('skeleton', 'This username is already used by another user.'),
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
                'message' => Yii::t('skeleton', 'This email address is already used by another user.'),
                'skipOnError' => true,
                'when' => fn () => $this->isAttributeChanged('email')
            ],
            [
                ['city', 'first_name', 'last_name'],
                'string',
                'max' => 50,
            ],
            [
                ['upload'],
                'file',
                'checkExtensionByMimeType' => $this->uploadCheckExtensionByMimeType,
                'extensions' => $this->uploadExtensions,
            ],
        ];
    }

    public function validatePassword(string $password): bool
    {
        return $this->password_hash && Yii::$app->getSecurity()->validatePassword($password . $this->password_salt, $this->password_hash);
    }

    public function beforeValidate(): bool
    {
        $this->status ??= static::STATUS_ENABLED;
        $this->timezone = $this->timezone ?: Yii::$app->getTimeZone();

        return parent::beforeValidate();
    }

    public function afterValidate(): void
    {
        if (!$this->requireName && !$this->name) {
            $this->name = null;
        }

        parent::afterValidate();
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->is_owner ??= !static::find()->exists();
                $this->generateAuthKey();
            }

            if ($this->upload) {
                $this->generatePictureFilename();
            }

            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            if (isset($changedAttributes['picture'])) {
                $this->deletePicture($changedAttributes['picture']);
            }
        }

        if ($this->upload) {
            $this->savePictureUpload();
        }

        parent::afterSave($insert, $changedAttributes);

        // Finally, unset upload, so additional updates won't try to upload again.
        $this->upload = null;
    }

    public function afterDelete(): void
    {
        if ($this->picture) {
            $this->deletePicture($this->picture);
        }

        parent::afterDelete();
    }

    public function getAdmin(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(static::class, ['id' => 'created_by_user_id']);
        return $query;
    }

    public function getAuthClients(): ActiveQuery
    {
        return $this->hasMany(AuthClient::class, ['user_id' => 'id']);
    }

    /**
     * @return UserQuery<static>
     */
    public static function find(): UserQuery
    {
        return Yii::createObject(UserQuery::class, [static::class]);
    }

    public static function findByEmail(string $email): UserQuery
    {
        return static::find()->whereLower([static::tableName() . '.[[email]]' => $email]);
    }

    public static function findByName(string $name): UserQuery
    {
        return static::find()->whereLower([static::tableName() . '.[[name]]' => $name]);
    }

    public function load($data, $formName = null): bool
    {
        // First load form data, then override upload via instance.
        $hasData = parent::load($data, $formName);
        $this->upload = $this->getUploadPath() ? UploadedFile::getInstance($this, 'upload') : null;

        return $hasData || $this->upload;
    }

    public function delete(): false|int
    {
        if ($this->isOwner()) {
            $this->addError('id', Yii::t('skeleton', 'This user is the website owner. Please transfer ownership to another user before deleting this user.'));
            return false;
        }

        return parent::delete();
    }

    public function afterPasswordChange(): void
    {
        $trail = Trail::create();
        $trail->model = User::class;
        $trail->model_id = (string)$this->id;
        $trail->type = Trail::TYPE_PASSWORD;
        $trail->insert();
    }

    public function generatePictureFilename(): void
    {
        $extension = $this->upload->extension ?? null;

        if (!$extension) {
            $extensions = array_intersect($this->uploadExtensions, FileHelper::getExtensionsByMimeType($this->upload->type ?? false));
            $extension = $extensions ? current($extensions) : null;
        }

        $this->picture = FileHelper::generateRandomFilename($extension, 12);
        $this->generatePictureFilenameInternal();
    }

    private function generatePictureFilenameInternal(): void
    {
        if (is_file($this->getUploadPath() . $this->picture)) {
            $this->generatePictureFilename();
        }
    }

    public function savePictureUpload(): bool
    {
        if (FileHelper::createDirectory($uploadPath = $this->getUploadPath())) {
            if ($this->upload->saveAs($uploadPath . $this->picture)) {
                if ($this->autorotatePicture) {
                    Image::autorotate($uploadPath . $this->picture)->save();
                }

                return true;
            }
        }

        return false;
    }

    public function deletePicture(?string $picture): bool
    {
        return $picture && FileHelper::unlink($this->getUploadPath() . $picture);
    }

    public function generatePasswordHash(string $password): void
    {
        $this->password_salt = Yii::$app->getSecurity()->generateRandomString(10);
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password . $this->password_salt);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }

    public function generateVerificationToken(): void
    {
        $this->verification_token = Yii::$app->getSecurity()->generateRandomString();
    }

    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->getSecurity()->generateRandomString();
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitials(): string
    {
        return $this->first_name && $this->last_name ? ($this->first_name[0] . $this->last_name[0]) : substr($this->name, 0, 2);
    }

    public function getUsername(): ?string
    {
        return $this->name;
    }

    public function getEmailConfirmationUrl(): ?string
    {
        if (!$this->verification_token) {
            return null;
        }

        /** @see AccountController::actionConfirm() */
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            'account/confirm',
            'email' => $this->email,
            'code' => $this->verification_token,
        ]);
    }

    public function getPasswordResetUrl(): ?string
    {
        if (!$this->password_reset_token) {
            return null;
        }

        /** @see AccountController::actionReset() */
        return Yii::$app->getUrlManager()->createAbsoluteUrl([
            'account/reset',
            'email' => $this->email,
            'code' => $this->password_reset_token,
        ]);
    }

    public function getTimezoneOffset(): string
    {
        $date = new \DateTime('now', new DateTimeZone($this->timezone ?? Yii::$app->getTimeZone()));
        return 'GMT ' . $date->format('P');
    }

    public function getUploadPath(): string|false
    {
        return $this->_uploadPath ? Yii::getAlias("@webroot/$this->_uploadPath") : false;
    }

    public function setUploadPath(string $uploadPath): void
    {
        $this->_uploadPath = trim($uploadPath, '/') . '/';
    }

    public function getBaseUrl(): string
    {
        return '/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $this->_uploadPath), '/');
    }

    public static function getStatuses(): array
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

    public function getStatusName(): string
    {
        if ($this->isOwner()) {
            return Yii::t('skeleton', 'Site Owner');
        }

        return static::getStatuses()[$this->status]['name'] ?? '';
    }

    public function getStatusIcon(): string
    {
        return !$this->isOwner() ? (static::getStatuses()[$this->status]['icon'] ?? '') : 'star';
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'password_hash',
            'password_salt',
            'auth_key',
            'verification_token',
            'password_reset_token',
            'google_2fa_secret',
            'login_count',
            'last_login',
            'created_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    public function getTrailModelName(): string
    {
        return $this->id ? $this->getUsername() : $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'User');
    }

    public function getTrailModelAdminRoute(): array|false
    {
        return $this->id ? ['/admin/user/update', 'id' => $this->id] : false;
    }

    public function isOwner(): bool
    {
        return (bool)$this->is_owner;
    }

    public function isUnconfirmed(): bool
    {
        return !$this->isOwner() && !empty($this->verification_token);
    }

    public static function getCountries(): array
    {
        return require(Yii::getAlias('@skeleton/messages/') . Yii::$app->language . '/countries.php');
    }

    public static function getLanguages(): array
    {
        $i18n = Yii::$app->getI18n();
        $languages = [];

        foreach (Yii::$app->getI18n()->getLanguages() as $language) {
            $languages[$language]['name'] = $i18n->getLabel($language);
        }

        return $languages;
    }

    public static function getTimezones(): array
    {
        return array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers());
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'id' => Yii::t('skeleton', 'ID'),
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
            'verification_token' => Yii::t('skeleton', 'Email verification code'),
            'login_count' => Yii::t('skeleton', 'Login count'),
            'last_login' => Yii::t('skeleton', 'Last login'),
            'is_owner' => Yii::t('skeleton', 'Website owner'),
            'updated_at' => Yii::t('skeleton', 'Updated'),
            'created_at' => Yii::t('skeleton', 'Created'),
            'upload' => Yii::t('skeleton', 'Picture'),
        ];
    }

    public function formName(): string
    {
        return 'User';
    }

    public static function tableName(): string
    {
        return '{{%user}}';
    }
}
