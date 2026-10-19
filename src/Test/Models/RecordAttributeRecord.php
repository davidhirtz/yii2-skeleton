<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Test\CustomAttributes\TestRecordCustomAttribute;
use Override;
use Yii;

/**
 * A record whose custom attributes name other rows of its table, at the top level, translated and inside nested
 * groups. Its table is created by whichever test uses it.
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $link
 * @property int|null $hero
 * @property int|null $hero_de
 * @property list<array<string, mixed>>|null $gallery
 * @property list<array<string, mixed>>|null $slides
 */
class RecordAttributeRecord extends ActiveRecord implements CustomAttributeInterface, TranslationInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TranslationTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            TestRecordCustomAttribute::make('link'),
            TestRecordCustomAttribute::make('hero')
                ->translatable(),
            GroupCustomAttribute::make('gallery')
                ->multiple()
                ->attributes([
                    TestRecordCustomAttribute::make('image'),
                    TextCustomAttribute::make('caption'),
                ]),
            GroupCustomAttribute::make('slides')
                ->multiple()
                ->attributes([
                    TextCustomAttribute::make('title'),
                    GroupCustomAttribute::make('items')
                        ->multiple()
                        ->attributes([
                            TestRecordCustomAttribute::make('image'),
                        ]),
                ]),
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['name'],
                'string',
            ],
        ];
    }

    #[Override]
    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    /**
     * @return I18nActiveQuery<static>
     */
    #[Override]
    public static function find(): I18nActiveQuery
    {
        return Yii::createObject(I18nActiveQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return 'record_attribute_record';
    }
}
