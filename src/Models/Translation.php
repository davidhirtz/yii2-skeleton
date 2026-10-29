<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Override;
use Yii;

/**
 * The source language stays in the model's own column, and `model_id` loads as a string, the column being an unsigned
 * bigint.
 *
 * @property int $id
 * @property string $model_class
 * @property int|string $model_id
 * @property string $language
 * @property string $attribute
 * @property string|null $value
 */
class Translation extends ActiveRecord
{
    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['model_class', 'model_id', 'language', 'attribute'],
                'required',
            ],
            [
                ['model_class'],
                'string',
                'max' => 255,
            ],
            [
                ['language'],
                'string',
                'max' => 16,
            ],
            [
                ['attribute'],
                'string',
                'max' => 64,
            ],
            [
                ['value'],
                'string',
                'max' => 65535,
            ],
        ];
    }

    /**
     * @return TranslationQuery<static>
     */
    #[Override]
    public static function find(): TranslationQuery
    {
        return Yii::createObject(TranslationQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%translation}}';
    }
}
