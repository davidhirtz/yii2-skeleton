<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\TypeSelectField;
use Override;
use Yii;

class TypeSelectFieldTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(AvailabilityRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'name' => 'string null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(AvailabilityRecord::tableName())
            ->execute();
    }

    public function testAnUnavailableTypeIsNotOffered(): void
    {
        $record = AvailabilityRecord::create();
        $html = $this->renderTypeSelectField($record);

        self::assertStringContainsString('>Always</option>', $html);
        self::assertStringNotContainsString('>Named</option>', $html);
        self::assertStringNotContainsString('>Never</option>', $html);

        $record->name = 'Name';
        $html = $this->renderTypeSelectField($record);

        self::assertStringContainsString('>Named</option>', $html);
    }

    private function renderTypeSelectField(AvailabilityRecord $record): string
    {
        return TypeSelectField::make()
            ->model($record)
            ->render();
    }
}

/**
 * @property int $type
 * @property string|null $name
 */
class AvailabilityRecord extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    final public const int TYPE_NAMED = 2;
    final public const int TYPE_NEVER = 3;

    #[Override]
    public static function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Always'),
            Type::make(self::TYPE_NAMED)
                ->name('Named')
                ->available(static fn (self $model): bool => (bool)$model->name),
            Type::make(self::TYPE_NEVER)
                ->name('Never')
                ->available(false),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_availability_record}}';
    }
}
