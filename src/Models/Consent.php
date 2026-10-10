<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\DateTime;
use Override;
use Yii;

/**
 * @property int $id
 * @property string $uuid
 * @property string $version
 * @property list<string> $categories
 * @property DateTime $created_at
 */
class Consent extends ActiveRecord
{
    public const int CATEGORIES_MAX_COUNT = 20;

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['uuid', 'version'],
                'required',
            ],
            [
                ['uuid'],
                'match',
                'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            ],
            [
                ['version'],
                'match',
                'pattern' => '/^[\w.-]{1,32}$/',
            ],
            [
                ['categories'],
                $this->validateCategories(...),
                'skipOnEmpty' => false,
            ],
        ];
    }

    public function validateCategories(): void
    {
        $categories = $this->getAttribute('categories') ?? [];

        if (!is_array($categories) || count($categories) > static::CATEGORIES_MAX_COUNT) {
            $this->addInvalidAttributeError('categories');
            return;
        }

        foreach ($categories as $category) {
            if (!is_string($category) || !preg_match('/^[\w-]{1,32}$/', $category)) {
                $this->addInvalidAttributeError('categories');
                return;
            }
        }

        $this->categories = array_values(array_unique($categories));
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->created_at = new DateTime();
        return parent::beforeSave($insert);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'uuid' => Yii::t('skeleton', 'CONSENT_UUID_LABEL'),
            'version' => Yii::t('skeleton', 'CONSENT_VERSION_LABEL'),
            'categories' => Yii::t('skeleton', 'CONSENT_CATEGORIES_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%consent}}';
    }
}
