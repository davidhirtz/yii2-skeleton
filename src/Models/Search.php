<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Search\SearchQuery;
use Override;
use Yii;

/**
 * `model_id` loads as a string, the column being an unsigned bigint.
 *
 * @property int $id
 * @property string $model_class
 * @property int|string $model_id
 * @property string $language
 * @property int|null $tenant_id
 * @property int $status
 * @property string $weight
 * @property string $title
 * @property string|null $content
 * @property DateTime $updated_at
 */
class Search extends ActiveRecord
{
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
