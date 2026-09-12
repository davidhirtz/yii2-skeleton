<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Search\SearchQuery;
use Override;
use Yii;

/**
 * One record per searchable model, id and language; written by {@see \Hirtz\Skeleton\Search\MysqlDriver}.
 *
 * @property int $id
 * @property string $model_class
 * @property int|string $model_id a string once loaded, the column is an unsigned bigint
 * @property string $language
 * @property int|null $tenant_id
 * @property int $status
 * @property float|string $weight
 * @property string $title
 * @property string|null $content
 * @property DateTime $updated_at
 */
class Search extends ActiveRecord
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
        ];
    }

    /**
     * @return SearchQuery<static>
     */
    #[Override]
    public static function find(): SearchQuery
    {
        return Yii::createObject(SearchQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%search}}';
    }
}
