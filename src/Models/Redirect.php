<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\I18n\Lang;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\BlameableBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Traits\UpdatedByUserTrait;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
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
class Redirect extends ActiveRecord implements TrailModelInterface, TypeAttributeInterface
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
        return [
            ...parent::rules(),
            [
                ['type'],
                DynamicRangeValidator::class,
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
        ];
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

    public function getDisplayName(): string
    {
        return Lang::t('skeleton', 'COMMON_MODEL_ID', [
            'model' => Lang::t('skeleton', 'REDIRECT_REDIRECT'),
            'id' => $this->id,
        ]);
    }

    #[Override]
    public function getTrailModelName(): string
    {
        return $this->id ? $this->getDisplayName() : Lang::t('skeleton', 'REDIRECT_REDIRECT');
    }

    public static function getTypes(): array
    {
        return [
            static::TYPE_MOVED_PERMANENTLY => [
                'name' => Lang::t('skeleton', 'REDIRECT_301_MOVED_PERMANENTLY'),
                'plural' => Lang::t('skeleton', 'REDIRECT_301_PERMANENT'),
                'icon' => 'forward',
            ],
            static::TYPE_FOUND => [
                'name' => Lang::t('skeleton', 'REDIRECT_302_TEMPORARY_REDIRECT'),
                'plural' => Lang::t('skeleton', 'REDIRECT_302_TEMPORARY'),
                'icon' => 'clock',
            ],
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'request_uri' => Lang::t('skeleton', 'REDIRECT_REQUEST_URI_LABEL'),
            'url' => Lang::t('skeleton', 'REDIRECT_URL_LABEL'),
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
