<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Override;
use Yii;

/**
 * The value of a translated attribute in a language other than {@see \yii\base\Application::$sourceLanguage}, one
 * record per model, language and attribute. The source language stays in the model's own column.
 *
 * A missing record, a `null` value and an empty string all mean "no translation".
 *
 * @property int $id
 * @property string $model
 * @property int $model_id
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
                ['model', 'model_id', 'language', 'attribute'],
                'required',
            ],
            [
                ['model'],
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
