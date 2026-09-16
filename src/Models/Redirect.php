<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\BlameableBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Models\Traits\UpdatedByUserTrait;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;

/**
 * @property int $id
 * @property string $request_uri
 * @property string $url
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 */
class Redirect extends ActiveRecord implements TrailModelInterface, TypeAttributeInterface
{
    use AdminModelTrait;
    use TypeAttributeTrait;
    use TrailModelTrait;
    use UpdatedByUserTrait;

    final public const string AUTH_REDIRECT = 'redirect';

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

        $this->request_uri = Url::sanitize($this->request_uri);
        $this->url = Url::sanitize($this->url);

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
     * A redirect must not point at another redirect's request URI, so the chain is followed to its end and the
     * resolved target is stored. **Trap:** the self-check has to run on the *resolved* target, not only on the
     * one that was assigned — a record renamed back to a URL it already had resolves through the redirect it
     * left behind and lands on its own request URI. This only catches a row whose two columns are in the same
     * shape; a host-qualified `request_uri` never compares equal to its relative `url`, so the writer of such a
     * row has to keep it out of a loop itself.
     */
    public function validateUrl(): void
    {
        $visited = [$this->request_uri => true];

        while ($this->url !== '' && !isset($visited[$this->url])) {
            $visited[$this->url] = true;

            $redirect = static::find()
                ->where(['request_uri' => $this->url])
                ->limit(1)
                ->one();

            if (!$redirect) {
                break;
            }

            $this->url = $redirect->url;
        }

        if ($this->url === $this->request_uri) {
            $this->addInvalidAttributeError('url');
        }
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/redirect/update', 'id' => $this->id] : false;
    }

    public function getAdminType(): string
    {
        return Yii::t('skeleton', 'REDIRECT_REDIRECT');
    }

    public function getBaseUrl(): string
    {
        return !parse_url($this->url, PHP_URL_HOST) ? '/' : '';
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return [
            Type::make(static::TYPE_MOVED_PERMANENTLY)
                ->name(Yii::t('skeleton', 'REDIRECT_301_MOVED_PERMANENTLY'))
                ->plural(Yii::t('skeleton', 'REDIRECT_301_PERMANENT'))
                ->icon('forward'),
            Type::make(static::TYPE_FOUND)
                ->name(Yii::t('skeleton', 'REDIRECT_302_TEMPORARY_REDIRECT'))
                ->plural(Yii::t('skeleton', 'REDIRECT_302_TEMPORARY'))
                ->icon('clock'),
        ];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'request_uri' => Yii::t('skeleton', 'REDIRECT_REQUEST_URI_LABEL'),
            'url' => Yii::t('skeleton', 'REDIRECT_URL_LABEL'),
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
