<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\BooleanCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\NumberCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class CustomAttributesTraitTest extends TestCase
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
        Yii::$container->clear(CustomAttributeRecord::class);

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(CustomAttributeRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'name' => 'string null',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(CustomAttributeRecord::tableName())
            ->execute();
    }

    public function testValuesRoundTripThroughTheJsonColumn(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->subtitle = 'Subtitle';
        $model->featured = '1';
        $model->count = '3';
        $model->layout = '2';
        $model->tags = ['a', 'c'];
        $model->note = 'Note';
        $model->subtitle_de = '';

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        self::assertSame([
            'subtitle' => 'Subtitle',
            'featured' => true,
            'count' => 3,
            'layout' => 2,
            'tags' => ['a', 'c'],
            'note' => 'Note',
        ], $this->findStoredValues($model));

        $loaded = CustomAttributeRecord::findOne($model->id);

        self::assertSame('Subtitle', $loaded->subtitle);
        self::assertTrue($loaded->featured);
        self::assertSame(3, $loaded->count);
        self::assertSame(2, $loaded->layout);
        self::assertSame(['a', 'c'], $loaded->tags);
        self::assertNull($loaded->subtitle_de);
    }

    public function testCustomAttributesAreVirtualAttributes(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;

        self::assertContains('subtitle', $model->attributes());
        self::assertContains('subtitle_de', $model->attributes());
        self::assertContains('name_de', $model->attributes());

        self::assertNotContains('subtitle', $model->getColumnAttributes());
        self::assertNotContains('subtitle_de', $model->getColumnAttributes());
        self::assertContains('custom_attributes', $model->getColumnAttributes());
    }

    public function testUnchangedSaveIssuesNoQuery(): void
    {
        $model = $this->createRecord();

        // Validation reads the translated attributes, which a single record would load lazily.
        $loaded = CustomAttributeRecord::find()
            ->withTranslations()
            ->where(['id' => $model->id])
            ->one();

        $queries = $this->countQueries(function () use ($loaded): void {
            self::assertSame(0, $loaded->update());
        });

        self::assertSame(0, $queries);

        $loaded->subtitle = 'Subtitle Updated';

        // The UPDATE plus the trail record it writes.
        $queries = $this->countQueries(function () use ($loaded): void {
            self::assertSame(1, $loaded->update());
        });

        self::assertSame(2, $queries);
    }

    public function testTrailLogsTheCustomAttributeRatherThanTheColumn(): void
    {
        $model = $this->createRecord();
        $trail = $this->findLatestTrail($model);

        self::assertSame('Subtitle', $trail->data['subtitle']);
        self::assertSame('Subtitle DE', $trail->data['subtitle_de']);
        self::assertArrayNotHasKey('custom_attributes', $trail->data);

        $model->subtitle = 'Subtitle Updated';
        self::assertSame(1, $model->update());

        $trail = $this->findLatestTrail($model);

        self::assertSame(['Subtitle', 'Subtitle Updated'], $trail->data['subtitle']);
        self::assertArrayNotHasKey('custom_attributes', $trail->data);
    }

    public function testSwitchingTheTypeKeepsTheStoredValue(): void
    {
        $model = $this->createRecord();

        $model->type = CustomAttributeRecord::TYPE_LINKS;
        self::assertSame(1, $model->update());
        self::assertArrayHasKey('subtitle', $this->findStoredValues($model));

        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->update();

        $loaded = CustomAttributeRecord::findOne($model->id);
        self::assertSame('Subtitle', $loaded->subtitle);
    }

    public function testLoadAssignsTheAttributesOfTheTypeInTheSamePost(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->getCustomAttributeDefinitions();

        self::assertTrue($model->load([
            'CustomAttributeRecord' => [
                'type' => CustomAttributeRecord::TYPE_LINKS,
                'subtitle' => 'Ignored',
                'links' => [['label' => 'Label', 'url' => 'https://example.com']],
            ],
        ]));

        self::assertSame(CustomAttributeRecord::TYPE_LINKS, $model->type);
        self::assertSame([['label' => 'Label', 'url' => 'https://example.com']], $model->links);
        self::assertFalse($model->hasAttribute('subtitle'));
    }

    public function testDuplicateCopiesTheCustomAttributes(): void
    {
        $model = $this->createRecord();
        $duplicate = DuplicateActiveRecord::create([$model]);

        self::assertInstanceOf(CustomAttributeRecord::class, $duplicate);
        self::assertSame('Subtitle', $duplicate->subtitle);
        self::assertSame('Subtitle DE', $duplicate->subtitle_de);
        self::assertSame(3, $duplicate->count);

        self::assertSame([
            'subtitle' => 'Subtitle',
            'subtitle_de' => 'Subtitle DE',
            'featured' => false,
            'count' => 3,
        ], $this->findStoredValues($duplicate));
    }

    public function testInvisibleAttributeIsUnsafeAndKeepsItsValue(): void
    {
        $model = $this->createRecord();

        self::assertFalse($model->isAttributeSafe('secret'));
        self::assertContains('secret', $model->attributes());

        $model->setAttribute('secret', 'Secret');
        self::assertSame(1, $model->update());

        $model->load(['CustomAttributeRecord' => ['secret' => 'Changed']]);

        self::assertSame('Secret', $model->secret);
    }

    public function testDisabledAttributeIsUnsafeButStillAnAttribute(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;

        $model->setAttribute('locked', 'Locked');
        $model->load(['CustomAttributeRecord' => ['locked' => 'Changed']]);

        self::assertFalse($model->isAttributeSafe('locked'));
        self::assertContains('locked', $model->attributes());
        self::assertSame('Locked', $model->locked);
    }

    public function testRequiredClosureValidatesOnlyWhenTheConditionHolds(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->name = 'Name';

        self::assertFalse($model->isAttributeRequired('note'));
        self::assertTrue($model->validate());

        $model->featured = true;

        self::assertFalse($model->validate());
        self::assertArrayHasKey('note', $model->getErrors());
    }

    public function testTranslatedCustomAttributeIsNotWrittenToTheTranslationTable(): void
    {
        $model = $this->createRecord();

        self::assertSame('Subtitle DE', $this->findStoredValues($model)['subtitle_de']);
        self::assertNull(Translation::find()->whereAttribute('subtitle')->one());

        $loaded = CustomAttributeRecord::findOne($model->id);

        self::assertSame('Subtitle DE', $loaded->getI18nAttribute('subtitle', 'de'));
        self::assertSame('Subtitle DE', $loaded->getI18nAttribute('subtitle', 'de', fallback: true));
        self::assertStringEndsWith('(DE)', $loaded->getAttributeLabel('subtitle_de'));

        $loaded->subtitle_de = '';
        $loaded->update();

        self::assertArrayNotHasKey('subtitle_de', $this->findStoredValues($model));
        self::assertSame('Subtitle', $loaded->getI18nAttribute('subtitle', 'de', fallback: true));
    }

    public function testRefreshRepopulatesAndAPartialSelectKeepsTheStoredValues(): void
    {
        $model = $this->createRecord();
        $loaded = CustomAttributeRecord::findOne($model->id);

        $loaded->subtitle = 'Not saved';
        $loaded->refresh();

        self::assertSame('Subtitle', $loaded->subtitle);
        self::assertFalse($loaded->isAttributeChanged('subtitle'));

        $partial = CustomAttributeRecord::find()
            ->select(['id', 'type', 'name'])
            ->where(['id' => $model->id])
            ->one();

        $partial->name = 'Name Updated';
        self::assertSame(1, $partial->update());

        self::assertSame('Subtitle', $this->findStoredValues($model)['subtitle']);
    }

    public function testValidationAppliesTheTypeSpecificRules(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->subtitle = str_repeat('a', 21);
        $model->count = 12;
        $model->layout = 9;

        self::assertFalse($model->validate());

        self::assertArrayHasKey('subtitle', $model->getErrors());
        self::assertArrayHasKey('count', $model->getErrors());
        self::assertArrayHasKey('layout', $model->getErrors());

        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->featured = '1';
        $model->count = '3';
        $model->note = 'Note';

        self::assertTrue($model->validate(), implode(' ', $model->getErrorSummary(true)));

        self::assertTrue($model->featured);
        self::assertSame(3, $model->count);
    }

    public function testDefaultIsAppliedToANewRecord(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->validate();

        self::assertFalse($model->featured);
    }

    public function testConfiguredDefinitionsReplaceThoseOfTheType(): void
    {
        Yii::$container->set(CustomAttributeRecord::class, [
            'customAttributes' => [TextCustomAttribute::make('configured')],
        ]);

        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_LINKS;
        $model->configured = 'Configured';

        self::assertSame(['configured'], array_keys($model->getCustomAttributeDefinitions()));
        self::assertFalse($model->hasAttribute('links'));
        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        // Applies to loaded records, which are instantiated through the container as well.
        self::assertSame('Configured', CustomAttributeRecord::findOne($model->id)->configured);
    }

    public function testSetCustomAttributesAcceptsAClosureAndResetsTheDefinitions(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;

        self::assertFalse($model->hasAttribute('configured'));
        self::assertFalse($model->isAttributeSafe('configured'));

        $model->setCustomAttributes(static fn (CustomAttributeRecord $model): array => [
            TextCustomAttribute::make('configured')->required(),
        ]);

        self::assertTrue($model->hasAttribute('configured'));
        self::assertTrue($model->isAttributeSafe('configured'));
        self::assertNotContains('subtitle', $model->attributes());

        self::assertFalse($model->validate());
        self::assertArrayHasKey('configured', $model->getErrors());
    }

    public function testDefinitionsWithoutTheColumnThrow(): void
    {
        $model = ColumnlessRecord::create();
        $model->type = ColumnlessRecord::TYPE_DEFAULT;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('has no "missing_column" column');

        $model->save();
    }

    public function testDefinitionCollidingWithAColumnThrows(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_COLUMN_COLLISION;

        $this->expectException(InvalidConfigException::class);
        $model->getCustomAttributeDefinitions();
    }

    public function testDefinitionCollidingWithATranslatedAttributeThrows(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_I18N_COLLISION;

        $this->expectException(InvalidConfigException::class);
        $model->getCustomAttributeDefinitions();
    }

    public function testInvalidDefinitionNameThrows(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_INVALID_NAME;

        $this->expectException(InvalidConfigException::class);
        $model->getCustomAttributeDefinitions();
    }

    public function testDuplicateDefinitionNameThrows(): void
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DUPLICATE_NAME;

        $this->expectException(InvalidConfigException::class);
        $model->getCustomAttributeDefinitions();
    }

    protected function createRecord(): CustomAttributeRecord
    {
        $model = CustomAttributeRecord::create();
        $model->type = CustomAttributeRecord::TYPE_DEFAULT;
        $model->name = 'Name';
        $model->subtitle = 'Subtitle';
        $model->subtitle_de = 'Subtitle DE';
        $model->count = 3;

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        return $model;
    }

    protected function findStoredValues(CustomAttributeRecord $model): array
    {
        $value = Yii::$app->getDb()->createCommand('SELECT [[custom_attributes]] FROM {{custom_attribute_test}} WHERE [[id]] = :id', [
            ':id' => $model->id,
        ])->queryScalar();

        return json_decode((string)$value, true) ?? [];
    }

    protected function findLatestTrail(CustomAttributeRecord $model): Trail
    {
        return Trail::find()
            ->where([
                'model_class' => $model::class,
                'model_id' => $model->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }
}

/**
 * @property int $id
 * @property int $type
 * @property string|null $name
 * @property string|null $name_de
 * @property array|null $custom_attributes
 * @property string|null $subtitle
 * @property string|null $subtitle_de
 * @property bool|string|null $featured
 * @property int|string|null $count
 * @property int|string|null $layout
 * @property list<string>|null $tags
 * @property string|null $secret
 * @property string|null $locked
 * @property string|null $note
 * @property array|null $links
 * @property string|null $configured
 */
class CustomAttributeRecord extends ActiveRecord implements
    CustomAttributeInterface,
    TrailModelInterface,
    TranslationInterface,
    TypeAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TrailModelTrait;
    use TranslationTrait;
    use TypeAttributeTrait;

    final public const int TYPE_LINKS = 2;
    final public const int TYPE_COLUMN_COLLISION = 3;
    final public const int TYPE_I18N_COLLISION = 4;
    final public const int TYPE_INVALID_NAME = 5;
    final public const int TYPE_DUPLICATE_NAME = 6;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEFAULT => [
                'name' => 'Default',
                'customAttributes' => fn (): array => [
                    TextCustomAttribute::make('subtitle')
                        ->max(20)
                        ->translatable(),
                    BooleanCustomAttribute::make('featured')
                        ->default(false),
                    NumberCustomAttribute::make('count')
                        ->max(10),
                    SelectCustomAttribute::make('layout')
                        ->options([1 => 'One', 2 => 'Two']),
                    SelectCustomAttribute::make('tags')
                        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
                        ->multiple(),
                    TextCustomAttribute::make('secret')
                        ->visible(false),
                    TextCustomAttribute::make('locked')
                        ->disabled(),
                    TextCustomAttribute::make('note')
                        ->required(static fn (self $model): bool => (bool)$model->featured),
                ],
            ],
            self::TYPE_LINKS => [
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
            self::TYPE_COLUMN_COLLISION => [
                'name' => 'Column collision',
                'customAttributes' => fn (): array => [TextCustomAttribute::make('name')],
            ],
            self::TYPE_I18N_COLLISION => [
                'name' => 'I18n collision',
                'customAttributes' => fn (): array => [TextCustomAttribute::make('name_de')],
            ],
            self::TYPE_INVALID_NAME => [
                'name' => 'Invalid name',
                'customAttributes' => fn (): array => [TextCustomAttribute::make('Sub Title')],
            ],
            self::TYPE_DUPLICATE_NAME => [
                'name' => 'Duplicate name',
                'customAttributes' => fn (): array => [
                    TextCustomAttribute::make('subtitle'),
                    TextCustomAttribute::make('subtitle'),
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
            ...$this->getI18nRules([
                [
                    ['name'],
                    'string',
                ],
            ]),
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
        return 'custom_attribute_test';
    }
}

class ColumnlessRecord extends CustomAttributeRecord
{
    #[Override]
    public function getCustomAttributesColumn(): string
    {
        return 'missing_column';
    }
}
