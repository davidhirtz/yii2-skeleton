<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\RecordCustomAttribute;
use Hirtz\Skeleton\Test\Models\RecordAttributeRecord;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

/**
 * Names another {@see RecordAttributeRecord}; only an id the owner does not start from has to exist.
 */
class TestRecordCustomAttribute extends RecordCustomAttribute
{
    #[Override]
    public function findRecords(Model $owner, array $ids): array
    {
        return RecordAttributeRecord::find()
            ->where(['id' => $ids])
            ->indexBy('id')
            ->all();
    }

    #[Override]
    protected function getRecordLabel(ActiveRecord $record): string
    {
        return (string)$record->getAttribute('name');
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            [
                'exist',
                'targetClass' => RecordAttributeRecord::class,
                'targetAttribute' => 'id',
                'when' => fn (Model $model, string $attribute): bool => !$this->isBaselineValue($model, $attribute, $model->$attribute),
            ],
        ];
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(InputField::make(), $owner);
    }
}
