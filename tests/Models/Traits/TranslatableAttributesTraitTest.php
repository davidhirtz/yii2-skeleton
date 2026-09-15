<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslatableAttributesTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class TranslatableAttributesTraitTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(TranslatableAttributeRecord::class);

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(TranslatableAttributeRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TranslatableAttributeRecord::tableName())
            ->execute();
    }

    public function testNothingIsTranslatableByDefault(): void
    {
        $record = $this->createRecord();

        self::assertSame([], $record->getTranslatableCustomAttributeNames());
        self::assertSame([], $record->getI18nAttributes());
        self::assertNotContains('name_de', $record->attributes());
    }

    public function testADeclaredAttributeBecomesTranslated(): void
    {
        $record = $this->createRecord(['name']);

        self::assertSame(['name'], $record->getTranslatableCustomAttributeNames());
        self::assertSame(['name'], $record->getI18nAttributes());
        self::assertContains('name_de', $record->attributes());
        self::assertNotContains('note_de', $record->attributes());
    }

    /**
     * The per-language value lives in the JSON column under its suffixed name, never in the `translation` table.
     */
    public function testTheTranslationIsStoredInTheJsonColumn(): void
    {
        $record = $this->createRecord(['name']);
        $record->name = 'Name';
        $record->name_de = 'Name (DE)';

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));

        $values = Yii::$app->getDb()
            ->createCommand('SELECT [[custom_attributes]] FROM ' . TranslatableAttributeRecord::tableName()
                . ' WHERE [[id]] = :id', [':id' => $record->id])
            ->queryScalar();

        self::assertSame([
            'name' => 'Name',
            'name_de' => 'Name (DE)',
        ], json_decode((string)$values, true));

        $loaded = TranslatableAttributeRecord::findOne($record->id);

        self::assertSame('Name', $loaded->getI18nAttribute('name'));
        self::assertSame('Name (DE)', $loaded->getI18nAttribute('name', 'de'));
    }

    /**
     * @param list<string> $translatableAttributes
     */
    private function createRecord(array $translatableAttributes = []): TranslatableAttributeRecord
    {
        Yii::$container->set(TranslatableAttributeRecord::class, [
            'translatableAttributes' => $translatableAttributes,
        ]);

        return TranslatableAttributeRecord::create();
    }
}

/**
 * @property int $id
 * @property array|null $custom_attributes
 * @property string|null $name
 * @property string|null $name_de
 * @property string|null $note
 */
class TranslatableAttributeRecord extends ActiveRecord implements CustomAttributeInterface, I18nAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TranslatableAttributesTrait;

    public function getCustomAttributes(): array
    {
        return [
            TextCustomAttribute::make('name')
                ->translatable($this->isTranslatableAttribute('name')),
            TextCustomAttribute::make('note')
                ->translatable($this->isTranslatableAttribute('note')),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'translatable_attribute_test';
    }
}
