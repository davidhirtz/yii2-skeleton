<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use Yii;

/**
 * @property int $id
 * @property int $type
 * @property string $request_uri
 * @property string $url
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 */
class Redirect extends ActiveRecord
{
    use TypeAttributeTrait;
    use UpdatedByUserTrait;

    /**
     * Types.
     */
    public const TYPE_DEFAULT = self::TYPE_MOVED_PERMANENTLY;
    public const TYPE_MOVED_PERMANENTLY = 301;
    public const TYPE_FOUND = 302;

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TrailBehavior' => TrailBehavior::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
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
                'validateUrl',
            ],
        ]);
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if ($this->type === null) {
            $this->type = static::TYPE_DEFAULT;
        }

        $this->request_uri = static::sanitizeUrl($this->request_uri);
        $this->url = static::sanitizeUrl($this->url);

        return parent::beforeValidate();
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
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
    public function validateUrl()
    {
        $redirect = static::find()
            ->where(['request_uri' => $this->url])
            ->limit(1)
            ->one();

        if ($redirect) {
            $this->url = $redirect->url;
        }
    }

    /**
     * @return array|false
     */
    public function getTrailModelAdminRoute()
    {
        return $this->getAdminRoute();
    }

    /**
     * @return RedirectActiveForm
     */
    public function getActiveForm()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return RedirectActiveForm::class;
    }

    /**
     * @return array|false
     */
    public function getAdminRoute()
    {
        return $this->id ? ['/admin/redirect/update', 'id' => $this->id] : false;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return !parse_url($this->url, PHP_URL_HOST) ? '/' : '';
    }

    /**
     * @param string $url
     * @return string|false
     */
    public static function sanitizeUrl($url)
    {
        return $url ? preg_replace('/\s+/', '%20', trim($url, '/ ')) : '';
    }

    /**
     * @return array[]
     */
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

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'request_uri' => Yii::t('skeleton', 'Request'),
            'url' => Yii::t('skeleton', 'Target URL'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function formName()
    {
        return 'Redirect';
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%redirect}}';
    }
}