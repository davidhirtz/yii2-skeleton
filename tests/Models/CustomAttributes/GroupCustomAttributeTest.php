<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class GroupCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$app->getDb()->createCommand()
            ->createTable(GroupRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    /**
     * `CREATE TABLE` commits the test transaction, so the records are removed by hand.
     */
    #[Override]
    protected function tearDown(): void
    {
        Translation::deleteAll(['model' => GroupRecord::class]);

        Yii::$app->getDb()->createCommand()
            ->dropTable(GroupRecord::tableName())
            ->execute();

        parent::tearDown();
    }

    public function testEmptyRowsAreDroppedAndTheRestReindexesInRequestOrder(): void
    {
        $model = $this->createRecord();
        $model->links = [
            7 => ['label' => 'Seven', 'url' => 'https://seven.example.com'],
            2 => ['label' => '', 'url' => ''],
            3 => ['label' => 'Three', 'url' => 'https://three.example.com'],
        ];

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        self::assertSame([
            ['label' => 'Seven', 'url' => 'https://seven.example.com'],
            ['label' => 'Three', 'url' => 'https://three.example.com'],
        ], $model->links);
    }

    public function testChildErrorsAreCopiedIntoTheOwnerAndOntoTheItem(): void
    {
        $model = $this->createRecord();
        $model->links = [
            ['label' => 'Valid', 'url' => 'https://example.com'],
            ['label' => 'Invalid', 'url' => 'not a url'],
        ];

        self::assertFalse($model->validate());
        self::assertArrayHasKey('links.1.url', $model->getErrors());

        $items = $model->getCustomAttributeItems('links');

        self::assertCount(2, $items);
        self::assertFalse($items[0]->hasErrors());
        self::assertTrue($items[1]->hasErrors('url'));
    }

    public function testMaxCountIsReportedOnTheGroup(): void
    {
        $model = $this->createRecord();
        $model->links = array_fill(0, 6, ['label' => 'Label', 'url' => 'https://example.com']);

        self::assertFalse($model->validate());
        self::assertArrayHasKey('links', $model->getErrors());
    }

    /**
     * The form renders the rows a minimum count demands, but an empty one is still dropped rather than stored.
     */
    public function testMinCountIsReportedOnTheGroupAndStoresNoEmptyRow(): void
    {
        $model = $this->createRecord();
        $model->type = GroupRecord::TYPE_REQUIRED;
        $model->links = [['label' => 'One'], ['label' => '']];

        self::assertFalse($model->validate());
        self::assertArrayHasKey('links', $model->getErrors());
        self::assertSame([['label' => 'One']], $model->links);

        $model->links = [['label' => 'One'], ['label' => 'Two']];

        self::assertTrue($model->validate(), implode(' ', $model->getErrorSummary(true)));
    }

    public function testRequiredChildBlocksTheSave(): void
    {
        $model = $this->createRecord();
        $model->links = [['label' => 'Label']];

        self::assertFalse($model->validate());
        self::assertArrayHasKey('links.0.url', $model->getErrors());
    }

    public function testItemFormNamesAndIdsCarryTheOwner(): void
    {
        $model = $this->createRecord();
        $model->links = [['label' => 'Label', 'url' => 'https://example.com']];

        $item = $model->getCustomAttributeItems('links')[0];

        self::assertSame('GroupRecord[links][0]', $item->formName());
        self::assertSame('GroupRecord[links][0][label]', Html::getInputName($item, 'label'));
        self::assertSame('grouprecord-links-0-label', Html::getInputId($item, 'label'));
        self::assertSame('GroupRecord[links][0][label_de]', Html::getInputName($item, 'label_de'));
    }

    public function testTranslatedChildValueIsStoredInsideTheItem(): void
    {
        $model = $this->createRecord();
        $model->links = [['label' => 'Label', 'label_de' => 'Label DE', 'url' => 'https://example.com']];

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        self::assertSame([
            'links' => [
                ['label' => 'Label', 'label_de' => 'Label DE', 'url' => 'https://example.com'],
            ],
        ], $this->findStoredValues($model));

        $loaded = GroupRecord::findOne($model->id);

        self::assertSame('Label DE', $loaded->getCustomAttributeItems('links')[0]->getAttribute('label_de'));
    }

    public function testSingleGroupStoresAnObject(): void
    {
        $model = $this->createRecord();
        $model->type = GroupRecord::TYPE_SINGLE;
        $model->meta = ['title' => 'Title'];

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));
        self::assertSame(['meta' => ['title' => 'Title']], $this->findStoredValues($model));

        $loaded = GroupRecord::findOne($model->id);

        self::assertSame(['title' => 'Title'], $loaded->meta);
        self::assertCount(1, $loaded->getCustomAttributeItems('meta'));
    }

    public function testNestedGroupRoundTrips(): void
    {
        $model = $this->createRecord();
        $model->type = GroupRecord::TYPE_NESTED;
        $model->rows = [
            [
                'name' => 'Row',
                'cells' => [
                    ['text' => 'One'],
                    ['text' => 'Two'],
                ],
            ],
        ];

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        $loaded = GroupRecord::findOne($model->id);

        self::assertSame([
            [
                'name' => 'Row',
                'cells' => [
                    ['text' => 'One'],
                    ['text' => 'Two'],
                ],
            ],
        ], $loaded->rows);

        $item = $loaded->getCustomAttributeItems('rows')[0];

        self::assertSame('GroupRecord[rows][0][cells][1]', $item->getCustomAttributeItems('cells')[1]->formName());
    }

    /**
     * The items are cached to keep the validated instances for the form; an assignment in between must rebuild them.
     */
    public function testReassigningTheGroupAfterValidationRebuildsTheItems(): void
    {
        $model = $this->createRecord();
        $model->links = [['label' => 'A', 'url' => 'https://a.example.com']];

        self::assertTrue($model->validate());
        $items = $model->getCustomAttributeItems('links');

        // Unchanged since validation, so the form gets the validated instances.
        self::assertSame($items, $model->getCustomAttributeItems('links'));

        $model->links = [['label' => 'B', 'url' => 'https://b.example.com']];

        self::assertTrue($model->validate());
        self::assertSame('B', $model->links[0]['label']);
        self::assertNotSame($items, $model->getCustomAttributeItems('links'));

        $model->links = [['label' => 'C', 'url' => 'not a url']];

        self::assertFalse($model->validate());
        self::assertTrue($model->getCustomAttributeItems('links')[0]->hasErrors('url'));
    }

    public function testTranslatingAGroupThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        GroupCustomAttribute::make('links')->translatable();
    }

    protected function createRecord(): GroupRecord
    {
        $model = GroupRecord::create();
        $model->type = GroupRecord::TYPE_DEFAULT;

        return $model;
    }

    protected function findStoredValues(GroupRecord $model): array
    {
        $value = Yii::$app->getDb()->createCommand('SELECT [[custom_attributes]] FROM {{group_test}} WHERE [[id]] = :id', [
            ':id' => $model->id,
        ])->queryScalar();

        return json_decode((string)$value, true) ?? [];
    }
}

/**
 * @property int $id
 * @property int $type
 * @property array|null $custom_attributes
 * @property array|null $links
 * @property array|null $meta
 * @property array|null $rows
 */
class GroupRecord extends ActiveRecord implements
    CustomAttributeInterface,
    TranslationInterface,
    TypeAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TranslationTrait;
    use TypeAttributeTrait;

    final public const int TYPE_SINGLE = 2;
    final public const int TYPE_NESTED = 3;
    final public const int TYPE_REQUIRED = 4;

    #[Override]
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEFAULT => [
                'name' => 'Links',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->maxCount(5)
                        ->attributes([
                            TextCustomAttribute::make('label')->translatable(),
                            UrlCustomAttribute::make('url')->required(),
                        ]),
                ],
            ],
            self::TYPE_SINGLE => [
                'name' => 'Meta',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('meta')
                        ->attributes([TextCustomAttribute::make('title')]),
                ],
            ],
            self::TYPE_REQUIRED => [
                'name' => 'Required',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->minCount(2)
                        ->attributes([TextCustomAttribute::make('label')]),
                ],
            ],
            self::TYPE_NESTED => [
                'name' => 'Nested',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('rows')
                        ->multiple()
                        ->attributes([
                            TextCustomAttribute::make('name'),
                            GroupCustomAttribute::make('cells')
                                ->multiple()
                                ->attributes([TextCustomAttribute::make('text')]),
                        ]),
                ],
            ],
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
        return 'group_test';
    }
}
