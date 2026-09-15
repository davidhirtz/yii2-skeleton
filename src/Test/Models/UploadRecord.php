<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;

/**
 * A record with an {@see UploadCustomAttribute}, declared here rather than in a test file so the model and the
 * controller test can both reach it. Its table is created by whichever test uses it.
 *
 * @property int $id
 * @property int $type
 * @property string|null $attachment
 * @property string|null $track
 * @property string|null $track_de
 */
class UploadRecord extends ActiveRecord implements
    CustomAttributeInterface,
    TranslationInterface,
    TypeAttributeInterface
{
    use AdminModelTrait;
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TranslationTrait;
    use TypeAttributeTrait;

    final public const int TYPE_RESTRICTED = 2;

    /**
     * @return array<array-key, mixed>|false
     */
    public function getAdminRoute(): array|false
    {
        return false;
    }

    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes(fn (): array => [
                    UploadCustomAttribute::make('attachment')
                        ->extensions(['txt', 'pdf']),
                    UploadCustomAttribute::make('track')
                        ->extensions(['vtt'])
                        ->translatable(),
                ]),
            Type::make(self::TYPE_RESTRICTED)
                ->name('Restricted')
                ->customAttributes(fn (): array => [
                    UploadCustomAttribute::make('attachment')
                        ->extensions(['pdf'])
                        ->maxSize(16),
                ]),
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
        return 'upload_test';
    }
}
