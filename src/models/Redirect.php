<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\models\traits\TrailModelTrait;
use davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use Override;
use Yii;

/**
 * @property int $id
 * @property int $type
 * @property string $request_uri
 * @property string $url
 * @property int|null $updated_by_user_id
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 */
class Redirect extends ActiveRecord implements TypeAttributeInterface, TrailModelInterface
{
    use TypeAttributeTrait;
    use TrailModelTrait;
    use UpdatedByUserTrait;

    final public const string AUTH_REDIRECT_CREATE = 'redirectCreate';

    final public const int TYPE_DEFAULT = self::TYPE_MOVED_PERMANENTLY;
    final public const int TYPE_MOVED_PERMANENTLY = 301;
    final public const int TYPE_FOUND = 302;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['type'],
                DynamicRangeValidator::class,
                'integerOnly' => false,
                'skipOnEmpty' => false,
            ],
            [
                ['request_uri'],
                'required',
            ],
            [
                ['request_uri', 'url'],
                'string',
                'max' => 250,
            ],
            [
                ['request_uri'],
                'unique',
            ],
            [
                ['url'],
                $this->validateUrl(...),
            ],
        ]);
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $this->type ??= static::TYPE_DEFAULT;

        $this->request_uri = static::sanitizeUrl($this->request_uri);
        $this->url = static::sanitizeUrl($this->url);

        return parent::beforeValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
        ]);

        return parent::beforeSave($insert);
    }

    /**
     * Makes sure the url is not pointing to another redirect, to eliminate unneeded redirect loops.
     */
    public function validateUrl(): void
    {
        $redirect = static::find()
            ->where(['request_uri' => $this->url])
            ->limit(1)
            ->one();

        if ($redirect) {
            $this->url = $redirect->url;
        }
    }

    public static function sanitizeUrl(false|string $url): string
    {
        return $url ? preg_replace('/\s+/', '%20', trim($url, '/ ')) : '';
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/redirect/update', 'id' => $this->id] : false;
    }

    public function getBaseUrl(): string
    {
        return !parse_url($this->url, PHP_URL_HOST) ? '/' : '';
    }

    public static function getTypes(): array
    {
        return [
            static::TYPE_MOVED_PERMANENTLY => [
                'name' => Yii::t('skeleton', '301 - Moved permanently'),
                'icon' => 'forward',
            ],
            static::TYPE_FOUND => [
                'name' => Yii::t('skeleton', '302 - Temporary redirect'),
                'icon' => 'clock',
            ],
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'request_uri' => Yii::t('skeleton', 'Request'),
            'url' => Yii::t('skeleton', 'Target URL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Redirect';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%redirect}}';
    }
}
